<?php

namespace App\Tests;

use App\Entity\Course;
use App\Exception\BillingUnavailableException;
use App\Security\User;
use App\Tests\Mock\BillingClientMock;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CourseRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use App\DataFixtures\AppFixtures;
use Doctrine\Common\DataFixtures\Loader;
use Liip\TestFixturesBundle\Test\FixturesTrait;
use Liip\TestFixturesBundle\Loader\FixtureLoader;


use Doctrine\Common\DataFixtures\Purger\ORMPurger as DoctrineORMPurger;
class CourseControllerTest extends WebTestCase
{
    private \Symfony\Bundle\FrameworkBundle\KernelBrowser $client;

    private EntityManagerInterface $em;
    private CourseRepository $courseRepository;

    private function createMockedClient(): \Symfony\Bundle\FrameworkBundle\KernelBrowser
    {
        $client = static::createClient();
        $client->disableReboot();

        $client->getContainer()->set('App\Service\BillingClient', new BillingClientMock());
        $client->getContainer()->set('App\Service\JwtTokenManager', new \App\Tests\Mock\FakeJwtTokenManager());

        return $client;
    }

    /**
     * @throws BillingUnavailableException
     */
    protected function setUp(): void
    {

        $this->client = static::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->courseRepository = self::getContainer()->get(CourseRepository::class); // Инициализация репозитория

        //////////////////////////////
        $this->client->disableReboot();

        $this->client->getContainer()->set('App\Service\BillingClient', new BillingClientMock());
        $this->client->getContainer()->set('App\Service\JwtTokenManager', new \App\Tests\Mock\FakeJwtTokenManager());
        $crawler = $this->client->request('GET', '/login');

        $form = $crawler->selectButton('Sign in')->form([
            'email' => 'admin@example.com',
            'password' => 'adminpass',

        ]);

        $this->client->submit($form);

        ///////////////////////////
        // Создаем загрузчик фикстур
        $loader = new Loader();
        $loader->addFixture(new AppFixtures());

        // Создаем и применяем очищение данных
        $purger = new ORMPurger($this->em);
        $purger->purge();

        // Загружаем фикстуры
        $executor = new \Doctrine\Common\DataFixtures\Executor\ORMExecutor($this->em, $purger);
        $executor->execute($loader->getFixtures());

        // Если нужно, можно еще очистить EntityManager для кэширования
        $this->em->clear();
    }

//    protected function getFixtures(): array
//    {
//        return [AppFixtures::class];
//    }

    public function testIndexPage(): void
    {
        $this->client->request('GET', '/courses');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Курсы');
        $this->assertSelectorCount(3, '.list-group-item');
    }

    public function testShowExistingCourse(): void
    {
        $course = $this->courseRepository->findOneBy(['code' => 'web-development']);
        $this->client->request('GET', '/courses/'.$course->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.card-header h1', 'Веб-разработка с нуля');
        $this->assertSelectorCount(3, '.list-group-item');
    }

    public function testShowNonExistentCourse(): void
    {
        $this->client->request('GET', '/courses/99999');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testNewCourseForm(): void
    {
        // Создаём мок-пользователя
//        $user = $this->createMock(User::class);
//        $user->method('getRoles')->willReturn(['ROLE_SUPER_ADMIN']);
//        $user->method('getUserIdentifier')->willReturn('admin@example.com');
        // Открываем сессию и вставляем туда нужный токен
//        $session = self::getContainer()->get('session');
//        $session->set('token', 'valid-token');
//        $session->save();
//
//        // Копируем сессионную куку в клиент
//        $this->client->getCookieJar()->set(new \Symfony\Component\BrowserKit\Cookie($session->getName(), $session->getId()));

        $crawler = $this->client->request('GET', '/courses/new');

        $form = $crawler->selectButton('Сохранить')->form([
            'course[code]' => 'test-course',
            'course[title]' => 'Новый курс тестирования',
            'course[description]' => 'Описание нового курса',
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/courses');

        $course = $this->courseRepository->findOneBy(['code' => 'test-course']);
        $this->assertNotNull($course);
    }

    public function testNewCourseFormValidation(): void
    {
        $crawler = $this->client->request('GET', '/courses/new');

        $form = $crawler->selectButton('Сохранить')->form([
            'course[code]' => '',
            'course[title]' => '',
            'course[description]' => 'Описание',
        ]);

        $this->client->submit($form);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorExists('.invalid-feedback');
    }

    public function testCourseLessons(): void
    {
        $course = $this->courseRepository->findOneBy(['code' => 'databases-sql']);
        $this->assertCount(4, $course->getLessons());

        $this->client->request('GET', '/courses/' . $course->getId());
        $this->assertSelectorCount(4, '.list-group-item');
        $this->assertSelectorTextContains('.list-group-item', 'Введение в базы данных');
    }

    public function testEditCourse(): void
    {
        // Используем уже созданный client
        $course = $this->courseRepository->findOneBy(['code' => 'python-basics']);

        $this->assertNotNull($course, 'Курс с таким кодом не найден');

        $crawler = $this->client->request('GET', '/courses/'.$course->getId().'/edit');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Редактирование курса');

        // Получаем форму и CSRF-токен
        $form = $crawler->selectButton('Сохранить изменения')->form([
            'course[title]' => 'Основы программирования на Python777',  // Изменяем название курса
            'course[description]' => 'Изучение базового синтаксиса и возможностей Python.', // Изменяем описание курса
            'course[code]' => 'python-basics',
            'course[type]' => 'free',
            'course[price]' => 480.0,
            'course[_token]' => $crawler->filter('input[name="course[_token]"]')->attr('value'),
        ]);

        $this->client->submit($form);

        //$this->assertResponseRedirects('/courses/'.$course->getId());
        $this->assertResponseRedirects('/courses');

        $this->em->clear();  // Сбрасываем кэш для получения актуальных данных
        $updatedCourse = $this->courseRepository->find($course->getId());  // Получаем обновленный курс из базы

        //echo "Updated course title: " . $updatedCourse->getTitle();
        //echo "Updated course description: " . $updatedCourse->getDescription();

        $this->assertEquals('Основы программирования на Python777', $updatedCourse->getTitle());
        $this->assertEquals('Изучение базового синтаксиса и возможностей Python.', $updatedCourse->getDescription());
    }


    public function testDeleteCourse(): void
    {
        // Используем уже созданный client
        $course = $this->courseRepository->findOneBy(['code' => 'databases-sql']);
        $this->assertNotNull($course, 'Курс с таким кодом не найден');

        // Получаем страницу с курсом, где будет кнопка удаления
        $crawler = $this->client->request('GET', '/courses/'.$course->getId());
        $this->assertResponseIsSuccessful();

        // Находим кнопку удаления и проверяем, что она есть на странице
        $deleteButton = $crawler->selectButton('Удалить курс');
        $this->assertNotNull($deleteButton, 'Кнопка удаления курса не найдена');

        // Получаем CSRF-токен для удаления
        $csrfToken = $crawler->filter('input[name="_token"]')->attr('value');
        $this->assertNotEmpty($csrfToken, 'CSRF токен не найден');

        // Отправляем POST-запрос с CSRF токеном для удаления курса
        $this->client->request('POST', '/courses/'.$course->getId(), [
            '_token' => $csrfToken,
        ]);

        // Проверяем, что произошел редирект на страницу курсов после удаления
        $this->assertResponseRedirects('/courses');

        // Проверяем, что курс действительно удален из базы данных
        $this->em->clear();  // Сбрасываем кэш для получения актуальных данных
        $deletedCourse = $this->courseRepository->find($course->getId());
        $this->assertNull($deletedCourse, 'Курс не был удален из базы данных');
    }









}