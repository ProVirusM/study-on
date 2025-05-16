<?php

namespace App\Tests\Mock;

use App\Repository\CourseRepository;
use App\Security\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Dto\UserRegisterDto;
use App\Form\RegistrationFormType;
use Symfony\Component\Form\Test\TypeTestCase;
class AuthControllerTest extends WebTestCase
{
    private function createMockedClient(): \Symfony\Bundle\FrameworkBundle\KernelBrowser
    {
        $client = static::createClient();
        $client->disableReboot();

        $client->getContainer()->set('App\Service\BillingClient', new BillingClientMock());
        $client->getContainer()->set('App\Service\JwtTokenManager', new \App\Tests\Mock\FakeJwtTokenManager());

        return $client;
    }
//    private function createMockedClient(): \Symfony\Bundle\FrameworkBundle\KernelBrowser
//    {
//        $client = static::createClient();
//        $client->disableReboot();
//
//        $mock = new BillingClientMock();
//        $client->getContainer()->set('App\Service\BillingClient', $mock);
//
//        return $client;
//    }

    public function testSuccessfulRegistration(): void
    {
        $client = $this->createMockedClient();

        $crawler = $client->request('GET', '/register');
        $form = $crawler->selectButton('Зарегистрироваться')->form();

        $form['registration_form[email]'] = 'new@example.com';
        $form['registration_form[password]'] = 'password123';
        $form['registration_form[confirmPassword]'] = 'password123';

        $client->submit($form);
        $this->assertResponseRedirects('/courses');
    }

    public function testProfileAccessWithValidToken(): void
    {
        $client = $this->createMockedClient();

        // Создаём и логиним пользователя
        $user = new User();
        $user->setEmail('test@example.com')
            ->setApiToken('valid-token')
            ->setRoles(['ROLE_USER']);
        $client->loginUser($user); // Аутентифицируем пользователя

        // Теперь делаем запрос
        $client->request('GET', '/profile');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Профиль пользователя');
    }

    public function testGuestCannotAccessCoursePage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/courses/new');

        $this->assertResponseRedirects('/login');
        //$this->assertResponseStatusCodeSame(401);
    }

    public function testGuestCannotAccessLessonPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/lesson/1');

        $this->assertResponseRedirects('/login');
        //$this->assertResponseStatusCodeSame(401);
    }

    public function testUserCannotAccessCourseEdit(): void
    {
        $client = $this->createMockedClient();

        $user = new User();
        $user->setEmail('user@example.com')
            ->setApiToken('valid-token')
            ->setRoles(['ROLE_USER']);
        $client->loginUser($user);

        $client->request('GET', '/courses/new');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testUserCannotAccessLessonEdit(): void
    {
        $client = $this->createMockedClient();

        $user = new User();
        $user->setEmail('user@example.com')
            ->setApiToken('valid-token')
            ->setRoles(['ROLE_USER']);
        $client->loginUser($user);

        $client->request('GET', '/lesson/new/1');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAccessToCourseFormWithoutAuth(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/courses/new');


        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The current node list is empty.');

        // Попытка найти форму, которой нет
        $crawler->filter('form')->first()->form();
    }

    public function testIndexPage(): void
    {
        $client = static::createClient();
        //$em = self::getContainer()->get(EntityManagerInterface::class);
        //$courseRepository = self::getContainer()->get(CourseRepository::class);
        $client->request('GET', '/courses');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Курсы');
        $this->assertSelectorCount(3, '.list-group-item');
    }

    public function testShowExistingCourse(): void
    {
        $client = static::createClient();
        //$em = self::getContainer()->get(EntityManagerInterface::class);
        $courseRepository = self::getContainer()->get(CourseRepository::class);
        $course = $courseRepository->findOneBy(['code' => 'web-development']);
        $client->request('GET', '/courses/' . $course->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.card-header h1', 'Веб-разработка с нуля');
        $this->assertSelectorCount(3, '.list-group-item');
    }

    public function testValSuccessfulRegistration(): void
    {
        $client = $this->createMockedClient();

        // Открываем страницу регистрации
        $crawler = $client->request('GET', '/register');

        // Получаем форму по кнопке
        $form = $crawler->selectButton('Зарегистрироваться')->form();

        // Заполняем поля формы, умышленно делая подтверждение пароля не совпадающим
        $form['registration_form[email]'] = 'new@example.com';
        $form['registration_form[password]'] = 'password123';
        $form['registration_form[confirmPassword]'] = 'password1234';

        // Отправляем форму
        $crawler = $client->submit($form);



        $this->assertStringContainsString('Пароли не совпадают', $client->getResponse()->getContent());
    }
}