<?php

namespace App\Tests;

use App\Entity\Course;
use App\Entity\Lesson;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\CourseRepository;
use App\Repository\LessonRepository;

class LessonControllerTest extends WebTestCase
{
    private static EntityManagerInterface $em;
    private static LessonRepository $lessonRepository;
    private static CourseRepository $courseRepository;

    public static function setUpBeforeClass(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        self::$em = $client->getContainer()->get(EntityManagerInterface::class);
        self::$lessonRepository = self::$em->getRepository(Lesson::class);
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

//    public function testIndexPage(): void
//    {
//        $this->initializeDatabase();
//        $client = static::createClient();
//
//
//        $course = self::$courseRepository->findOneBy(['code' => 'web-development']);
//        $client->request('GET', '/lesson', ['course_id' => $course->getId()]);
//
//        $this->assertResponseIsSuccessful();
//        $this->assertSelectorTextContains('h1', 'Список уроков');
//        $this->assertSelectorTextContains('table', 'Введение в веб-разработку');
//    }

    public function testNewLesson(): void
    {
        $this->initializeDatabase();
        $client = static::createClient();

        // Берем курс из фикстур
        $course = self::$courseRepository->findOneBy(['code' => 'python-basics']);

        $crawler = $client->request('GET', '/lesson/new/'.$course->getId());
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Сохранить')->form([
            'lesson[title]' => 'Новый тестовый урок',
            'lesson[content]' => 'Содержание нового урока',
            'lesson[orderNumber]' => 1,
        ]);

        $client->submit($form);
        $this->assertResponseRedirects('/courses/'.$course->getId());

        $lesson = self::$lessonRepository->findOneBy(['title' => 'Новый тестовый урок']);
        $this->assertNotNull($lesson);
    }

    public function testNewLessonValidation(): void
    {
        $this->initializeDatabase();
        $client = static::createClient();

        $course = self::$courseRepository->findOneBy(['code' => 'databases-sql']);
        $crawler = $client->request('GET', '/lesson/new/'.$course->getId());

        $form = $crawler->selectButton('Сохранить')->form([
            'lesson[title]' => '', // Пустое название
            'lesson[content]' => 'Содержание без названия',
            'lesson[orderNumber]' => 1,
        ]);

        $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorExists('.invalid-feedback');
    }

    public function testShowLesson(): void
    {
        $this->initializeDatabase();
        $client = static::createClient();

        // Берем урок из фикстур
        $lesson = self::$lessonRepository->findOneBy(['title' => 'Введение в базы данных']);

        $client->request('GET', '/lesson/'.$lesson->getId());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.card-header h1', $lesson->getTitle());
    }

    public function testShowNonExistentLesson(): void
    {
        $client = static::createClient();
        $client->request('GET', '/lesson/99999');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

//    public function testEditLesson(): void
//    {
//        $this->initializeDatabase();
//        $client = static::createClient();
//
//        $lesson = self::$lessonRepository->findOneBy(['title' => 'Запросы SELECT']);
//        $crawler = $client->request('GET', '/lesson/'.$lesson->getId().'/edit');
//        $this->assertResponseIsSuccessful();
//
//        $form = $crawler->selectButton('Сохранить изменения')->form([
//            'lesson[title]' => 'Обновленное название урока',
//            'lesson[content]' => 'Обновленное содержание урока',
//            'lesson[orderNumber]' => 2,
//        ]);
//
//        $client->submit($form);
//        $this->assertResponseRedirects('/lesson/'.$lesson->getId());
//
//        self::$em->clear();
//        $updatedLesson = self::$lessonRepository->find($lesson->getId());
//        $this->assertEquals('Обновленное название урока', $updatedLesson->getTitle());
//    }
    public function testEditLesson(): void
    {
        $this->initializeDatabase();
        $client = static::createClient();

        // Создаем тестовый курс и урок
        $course = new Course();
        $course->setCode('test-course');
        $course->setTitle('Тестовый курс');
        $course->setDescription('Описание тестового курса');

        $lesson = new Lesson();
        $lesson->setTitle('Тестовый урок');
        $lesson->setContent('Содержание тестового урока');
        $lesson->setOrderNumber(1);
        $lesson->setCourse($course);

        self::$em->persist($course);
        self::$em->persist($lesson);
        self::$em->flush();

        $crawler = $client->request('GET', '/lesson/'.$lesson->getId().'/edit');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Сохранить изменения')->form([
            'lesson[title]' => 'Обновленное название урока',
            'lesson[content]' => 'Обновленное содержание урока',
            'lesson[orderNumber]' => 2,
        ]);

        $client->submit($form);
        $this->assertResponseRedirects('/lesson/'.$lesson->getId());

        self::$em->clear();
        $updatedLesson = self::$lessonRepository->find($lesson->getId());
        $this->assertEquals('Обновленное название урока', $updatedLesson->getTitle());
    }
//    public function testDeleteLesson(): void
//    {
//        $this->initializeDatabase();
//        $client = static::createClient();
//
//        $lesson = self::$lessonRepository->findOneBy(['title' => 'Связи между таблицами']);
//        $courseId = $lesson->getCourse()->getId();
//
//        // Включаем сессии для работы с CSRF
//        $client->enableProfiler();
//
//        // Получаем страницу урока и находим форму удаления
//        $crawler = $client->request('GET', '/lesson/'.$lesson->getId());
//        $form = $crawler->filter('form[action*="delete"]')->form();
//
//        // Отправляем форму удаления
//        $client->submit($form);
//
//        $this->assertResponseRedirects('/courses/'.$courseId);
//
//        // Проверяем, что урок удален из базы
//        self::$em->clear();
//        $deletedLesson = self::$lessonRepository->find($lesson->getId());
//        $this->assertNull($deletedLesson);
//    }
}