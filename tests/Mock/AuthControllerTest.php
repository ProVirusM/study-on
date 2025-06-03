<?php

namespace App\Tests\Mock;

use AllowDynamicProperties;
use App\Repository\CourseRepository;
use App\Security\User;
use App\Tests\Mock\BillingClientMock;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Dto\UserRegisterDto;
use App\Form\RegistrationFormType;
use Symfony\Component\Form\Test\TypeTestCase;



#[AllowDynamicProperties] class AuthControllerTest extends WebTestCase
{


    private function createMockedClient(): \Symfony\Bundle\FrameworkBundle\KernelBrowser
    {
        $client = static::createClient();
        $client->disableReboot();

        $client->getContainer()->set('App\Service\BillingClient', new BillingClientMock());
        $client->getContainer()->set('App\Service\JwtTokenManager', new \App\Tests\Mock\FakeJwtTokenManager());

        return $client;
    }
    public function testLoginAsAdminThroughForm(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $client->getContainer()->set('App\Service\BillingClient', new \App\Tests\Mock\BillingClientMock());
        $client->getContainer()->set('App\Service\JwtTokenManager', new \App\Tests\Mock\FakeJwtTokenManager());

        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Sign in')->form([
            'email' => 'admin@example.com',
            'password' => 'adminpass',

        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/profile');
        $client->followRedirect();

        $this->assertSelectorTextContains('body', 'Администратор');
    }


    public function testSuccessfulRegistration(): void
    {
        $this->client = static::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->courseRepository = self::getContainer()->get(CourseRepository::class);

        $this->client->disableReboot();

        $this->client->getContainer()->set('App\Service\BillingClient', new BillingClientMock());
        $this->client->getContainer()->set('App\Service\JwtTokenManager', new \App\Tests\Mock\FakeJwtTokenManager());

        $crawler = $this->client->request('GET', '/register');
        $form = $crawler->selectButton('Зарегистрироваться')->form();

        $form['registration_form[email]'] = 'new@example.com';
        $form['registration_form[password]'] = 'password123';
        $form['registration_form[confirmPassword]'] = 'password123';

        $this->client->submit($form);
        $this->assertResponseRedirects('/courses');

    }


    public function testProfileAccessWithValidToken(): void
    {
        $this->client = static::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->courseRepository = self::getContainer()->get(CourseRepository::class);

        $this->client->disableReboot();

        $this->client->getContainer()->set('App\Service\BillingClient', new BillingClientMock());
        $this->client->getContainer()->set('App\Service\JwtTokenManager', new \App\Tests\Mock\FakeJwtTokenManager());

        // Логинимся
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Sign in')->form([
            'email' => 'user@example.com',
            'password' => 'adminpass',
        ]);
        $this->client->submit($form);

        // Теперь делаем запрос
        $this->client->request('GET', '/profile');

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
        $this->client = static::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->courseRepository = self::getContainer()->get(CourseRepository::class);

        $this->client->disableReboot();

        $this->client->getContainer()->set('App\Service\BillingClient', new BillingClientMock());
        $this->client->getContainer()->set('App\Service\JwtTokenManager', new \App\Tests\Mock\FakeJwtTokenManager());

        // Логинимся
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Sign in')->form([
            'email' => 'user@example.com',
            'password' => 'adminpass',
        ]);
        $this->client->submit($form);

        $this->client->request('GET', '/courses/new');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testUserCannotAccessLessonEdit(): void
    {
        $this->client = static::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->courseRepository = self::getContainer()->get(CourseRepository::class);

        $this->client->disableReboot();

        $this->client->getContainer()->set('App\Service\BillingClient', new BillingClientMock());
        $this->client->getContainer()->set('App\Service\JwtTokenManager', new \App\Tests\Mock\FakeJwtTokenManager());

        // Логинимся
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Sign in')->form([
            'email' => 'user@example.com',
            'password' => 'adminpass',
        ]);
        $this->client->submit($form);


        $this->client->request('GET', '/lesson/new/1');
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
    public function testCoursePurchaseFromList(): void
    {
        $this->client = static::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->courseRepository = self::getContainer()->get(CourseRepository::class);

        $this->client->disableReboot();

        $this->client->getContainer()->set('App\Service\BillingClient', new BillingClientMock());
        $this->client->getContainer()->set('App\Service\JwtTokenManager', new \App\Tests\Mock\FakeJwtTokenManager());

        // Логинимся
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Sign in')->form([
            'email' => 'admin@example.com',
            'password' => 'adminpass',
        ]);
        $this->client->submit($form);

        // Открываем список курсов
        $crawler = $this->client->request('GET', '/courses');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Курсы');

        // Кликаем по курсу
        $link = $crawler->selectLink('Веб-разработка с нуля')->link();
        $crawler = $this->client->click($link);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Веб-разработка с нуля');

        // Проверка кнопки открытия модального окна
        $this->assertSelectorExists('button[data-bs-target="#confirmPurchaseModal"]');

        // Извлекаем ID курса из URL
        $currentUrl = $this->client->getRequest()->getUri(); // http://localhost/courses/id
        $path = parse_url($currentUrl, PHP_URL_PATH);        // /courses/id
        $courseId = basename($path);                         // id

        // Симулируем подтверждение покупки
        $this->client->request('GET', '/courses/' . $courseId . '/pay');

        // Проверяем редирект и flash-сообщение
        $this->assertResponseRedirects('/courses/' . $courseId);
        $this->client->followRedirect();

        $this->assertSelectorExists('.alert-success');
        $this->assertSelectorTextContains('.alert-success', 'Оплата прошла успешно');
    }
    public function testTransactionHistoryButtonLeadsToCorrectPage(): void
    {
        $this->client = static::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->courseRepository = self::getContainer()->get(CourseRepository::class);

        $this->client->disableReboot();

        $this->client->getContainer()->set('App\Service\BillingClient', new BillingClientMock());
        $this->client->getContainer()->set('App\Service\JwtTokenManager', new \App\Tests\Mock\FakeJwtTokenManager());

        // Логинимся
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Sign in')->form([
            'email' => 'admin@example.com',
            'password' => 'adminpass',
        ]);
        $this->client->submit($form);

        // Заходим на страницу профиля
        $crawler = $this->client->request('GET', '/profile');

        // Проверяем, что кнопка "История транзакций" существует
        $link = $crawler->selectLink('История транзакций')->link();
        $this->assertNotNull($link, 'Кнопка "История транзакций" не найдена.');

        // Переходим по ссылке
        $crawler = $this->client->click($link);

        // Проверяем, что страница успешно загрузилась
        $this->assertResponseIsSuccessful();

        // Убедимся, что заголовок на странице — "История транзакций"
        $this->assertSelectorTextContains('h1', 'История транзакций');
    }

}