<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\Course;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CourseRepository;

class CourseControllerTest extends WebTestCase
{
    private static EntityManagerInterface $em;
    private static CourseRepository $courseRepository;

    public static function setUpBeforeClass(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        self::$em = $client->getContainer()->get(EntityManagerInterface::class);
        self::$courseRepository = self::$em->getRepository(Course::class);
    }

    protected function setUp(): void
    {
        parent::setUp();
        self::ensureKernelShutdown();
        self::$em->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        self::$em->getConnection()->rollBack();
        parent::tearDown();
    }

    private function initializeDatabase(): void
    {
        $connection = self::$em->getConnection();

        try {

            $connection->executeStatement('DELETE FROM lesson');
            $connection->executeStatement('DELETE FROM course');

            $fixtures = new \App\DataFixtures\AppFixtures();
            $fixtures->load(self::$em);
        } catch (\Exception $e) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }
            throw $e;
        }
    }

    public function testIndexPage(): void
    {
        $this->initializeDatabase();

        $client = static::createClient();
        $client->request('GET', '/courses');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Курсы');
        $this->assertSelectorCount(3, '.list-group-item');
    }

    public function testShowExistingCourse(): void
    {
        $this->initializeDatabase();
        $client = static::createClient();
        $course = self::$courseRepository->findOneBy(['code' => 'web-development']);

        $client->request('GET', '/courses/'.$course->getId());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.card-header h1', 'Веб-разработка с нуля');
        $this->assertSelectorCount(3, '.list-group-item');
    }

    public function testShowNonExistentCourse(): void
    {
        $client = static::createClient();
        $client->request('GET', '/courses/99999');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testNewCourseForm(): void
    {
        $this->initializeDatabase();
        $client = static::createClient();
        $crawler = $client->request('GET', '/courses/new');

        $form = $crawler->selectButton('Сохранить')->form([
            'course[code]' => 'test-course',
            'course[title]' => 'Новый курс тестирования',
            'course[description]' => 'Описание нового курса',
        ]);

        $client->submit($form);
        $this->assertResponseRedirects('/courses');

        $course = self::$courseRepository->findOneBy(['code' => 'test-course']);
        $this->assertNotNull($course);
    }

    public function testNewCourseFormValidation(): void
    {
        $this->initializeDatabase();
        $client = static::createClient();
        $crawler = $client->request('GET', '/courses/new');

        // Пробуем отправить форму без обязательных полей
        $form = $crawler->selectButton('Сохранить')->form([
            'course[code]' => '',
            'course[title]' => '',
            'course[description]' => 'Описание',
        ]);

        $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorExists('.invalid-feedback');
    }

//    public function testEditCourse(): void
//    {
//        $this->initializeDatabase();
//        $client = static::createClient();
//        $course = self::$courseRepository->findOneBy(['code' => 'python-basics']);
//
//
//        $this->assertNotNull($course, 'Курс с таким кодом не найден');
//
//
//        $crawler = $client->request('GET', '/courses/'.$course->getId().'/edit');
//        $this->assertResponseIsSuccessful();
//        $this->assertSelectorTextContains('h1', 'Редактирование курса');
//
//        // 2. Получаем форму и CSRF-токен
//        $form = $crawler->selectButton('Сохранить изменения')->form([
//            'course[title]' => 'Основы программирования на Python777',  // Изменяем название курса
//            'course[description]' => 'Изучение базового синтаксиса и возможностей Python.', // Изменяем описание курса
//            'course[code]' => 'python-basics',
//            'course[_token]' => $crawler->filter('input[name="course[_token]"]')->attr('value'),
//        ]);
//
//
//        $client->submit($form);
//
//
//        $this->assertResponseRedirects('/courses/'.$course->getId());
//
//
//        self::$em->clear();  // Сбрасываем кэш для получения актуальных данных
//        $updatedCourse = self::$courseRepository->find($course->getId());  // Получаем обновленный курс из базы
//
//
//        echo "Updated course title: " . $updatedCourse->getTitle();
//        echo "Updated course description: " . $updatedCourse->getDescription();
//
//        $this->assertEquals('Основы программирования на Python777', $updatedCourse->getTitle());
//        $this->assertEquals('Изучение базового синтаксиса и возможностей Python.', $updatedCourse->getDescription());
//    }








//    public function testDeleteCourse(): void
//    {
//        $this->initializeDatabase();
//        $client = static::createClient();
//
//
//        $client->request('GET', '/');
//
//
//        $course = self::$courseRepository->findOneBy(['code' => 'databases-sql']);
//        $courseId = $course->getId();
//
//
//        $csrfTokenManager = $client->getContainer()->get('security.csrf.token_manager');
//        $csrfToken = $csrfTokenManager->getToken('delete' . $courseId)->getValue();
//
//        // Отправляем POST-запрос с CSRF токеном для удаления курса
//        $client->request('POST', '/courses/' . $courseId, [
//            '_token' => $csrfToken,
//        ]);
//
//        // Проверяем, что произошел редирект после удаления
//        $this->assertResponseRedirects('/courses');
//
//        // Проверяем, что курс действительно удален из базы данных
//        self::$em->clear();
//        $deletedCourse = self::$courseRepository->find($courseId);
//        $this->assertNull($deletedCourse);
//    }
//
//




    public function testCourseLessons(): void
    {
        $this->initializeDatabase();
        $client = static::createClient();
        $course = self::$courseRepository->findOneBy(['code' => 'databases-sql']);

        $this->assertCount(4, $course->getLessons());

        $client->request('GET', '/courses/'.$course->getId());
        $this->assertSelectorCount(4, '.list-group-item');
        $this->assertSelectorTextContains('.list-group-item', 'Введение в базы данных');
    }
}