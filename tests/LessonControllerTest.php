<?php

namespace App\Tests;

use App\DataFixtures\AppFixtures;
use App\Entity\Course;
use App\Entity\Lesson;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\CourseRepository;
use App\Repository\LessonRepository;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
class LessonControllerTest extends WebTestCase
{
    private EntityManagerInterface $em;
    private CourseRepository $courseRepository;
    private LessonRepository $lessonRepository;
    private $client;

    // Используем setUp() для инициализации данных
    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        // Получаем контейнер и сервисы
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->courseRepository = self::getContainer()->get(CourseRepository::class);
        $this->lessonRepository = self::getContainer()->get(LessonRepository::class);

        // Загружаем фикстуры
        $loader = new Loader();
        $loader->addFixture(new AppFixtures());

        // Создаем и применяем очищение данных
        $purger = new ORMPurger($this->em);
        $purger->purge();

        // Загружаем фикстуры
        $executor = new \Doctrine\Common\DataFixtures\Executor\ORMExecutor($this->em, $purger);
        $executor->execute($loader->getFixtures());

        // Очистка EntityManager
        $this->em->clear();
    }

    public function testNewLesson(): void
    {
        $course = $this->courseRepository->findOneBy(['code' => 'python-basics']);

        $crawler = $this->client->request('GET', '/lesson/new/'.$course->getId());
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Сохранить')->form([
            'lesson[title]' => 'Новый тестовый урок',
            'lesson[content]' => 'Содержание нового урока',
            'lesson[orderNumber]' => 1,
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/courses/'.$course->getId());

        $lesson = $this->lessonRepository->findOneBy(['title' => 'Новый тестовый урок']);
        $this->assertNotNull($lesson);
    }

    public function testNewLessonValidation(): void
    {
        $course = $this->courseRepository->findOneBy(['code' => 'databases-sql']);
        $crawler = $this->client->request('GET', '/lesson/new/'.$course->getId());

        $form = $crawler->selectButton('Сохранить')->form([
            'lesson[title]' => '', // Пустое название
            'lesson[content]' => 'Содержание без названия',
            'lesson[orderNumber]' => 1,
        ]);

        $this->client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorExists('.invalid-feedback');
    }

    public function testShowLesson(): void
    {
        $lesson = $this->lessonRepository->findOneBy(['title' => 'Введение в базы данных']);

        $this->client->request('GET', '/lesson/'.$lesson->getId());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.card-header h1', $lesson->getTitle());
    }

    public function testShowNonExistentLesson(): void
    {
        $this->client->request('GET', '/lesson/99999');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testEditLesson(): void
    {
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

        $this->em->persist($course);
        $this->em->persist($lesson);
        $this->em->flush();

        $crawler = $this->client->request('GET', '/lesson/'.$lesson->getId().'/edit');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Сохранить изменения')->form([
            'lesson[title]' => 'Обновленное название урока',
            'lesson[content]' => 'Обновленное содержание урока',
            'lesson[orderNumber]' => 2,
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/lesson/'.$lesson->getId());

        $this->em->clear();
        $updatedLesson = $this->lessonRepository->find($lesson->getId());
        $this->assertEquals('Обновленное название урока', $updatedLesson->getTitle());
    }

    public function testDeleteLesson(): void
    {
        // Получаем урок, который нужно удалить
        $lesson = $this->lessonRepository->findOneBy(['title' => 'Связи между таблицами']);
        $courseId = $lesson->getCourse()->getId();

        // Включаем сессии для работы с CSRF
        $this->client->enableProfiler();

        // Получаем страницу урока и находим форму удаления
        $crawler = $this->client->request('GET', '/lesson/'.$lesson->getId());
        $this->assertResponseIsSuccessful();

        // Находим форму удаления
        $form = $crawler->selectButton('Удалить')->form();

        // Вставляем CSRF токен в форму
        $csrfToken = $crawler->filter('input[name="_token"]')->attr('value');
        $form['_token'] = $csrfToken;

        // Отправляем форму удаления
        $this->client->submit($form);

        // Проверяем редирект на страницу курса
        $this->assertResponseRedirects('/courses/'.$courseId);

        // Проверяем, что урок удален из базы данных
        $this->em->clear(); // Очистка Entity Manager
        $deletedLesson = $this->lessonRepository->find($lesson->getId());
        $this->assertNull($deletedLesson, 'Урок не был удален');
    }


}
