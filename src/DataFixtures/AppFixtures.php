<?php

namespace App\DataFixtures;

use App\Entity\Course;
use App\Entity\Lesson;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Список курсов с описанием
        $coursesData = [
            [
                'code' => 'web-development',
                'title' => 'Веб-разработка с нуля',
                'description' => 'Курс по созданию сайтов с использованием HTML, CSS и JavaScript.',
                'lessons' => [
                    ['title' => 'Введение в веб-разработку', 'content' => 'Основы создания сайтов и их работы.'],
                    ['title' => 'HTML и структура страниц', 'content' => 'Разметка контента с помощью HTML.'],
                    ['title' => 'CSS: Стилизация страниц', 'content' => 'Работа с цветами, шрифтами и макетом.'],
                ]
            ],
            [
                'code' => 'python-basics',
                'title' => 'Основы программирования на Python',
                'description' => 'Изучение базового синтаксиса и возможностей Python.',
                'lessons' => [
                    ['title' => 'Переменные и типы данных', 'content' => 'Что такое переменные, строки и числа.'],
                    ['title' => 'Условные конструкции', 'content' => 'Использование if-else в Python.'],
                    ['title' => 'Циклы и списки', 'content' => 'Как работать с массивами данных.'],
                ]
            ],
            [
                'code' => 'databases-sql',
                'title' => 'Работа с базами данных',
                'description' => 'Курс по SQL, основам реляционных баз данных и их управлению.',
                'lessons' => [
                    ['title' => 'Введение в базы данных', 'content' => 'Основные понятия SQL и реляционных БД.'],
                    ['title' => 'Запросы SELECT', 'content' => 'Как выбирать данные из таблицы.'],
                    ['title' => 'Операции INSERT, UPDATE, DELETE', 'content' => 'Добавление, обновление и удаление данных.'],
                    ['title' => 'Связи между таблицами', 'content' => 'Работа с JOIN и ключами.'],
                ]
            ]
        ];

        // Создаем курсы и уроки
        foreach ($coursesData as $courseIndex => $courseInfo) {
            $course = new Course();
            $course->setCode($courseInfo['code']);
            $course->setTitle($courseInfo['title']);
            $course->setDescription($courseInfo['description']);
            $manager->persist($course);

            foreach ($courseInfo['lessons'] as $lessonIndex => $lessonInfo) {
                $lesson = new Lesson();
                $lesson->setCourse($course);
                $lesson->setTitle($lessonInfo['title']);
                $lesson->setContent($lessonInfo['content']);
                $lesson->setOrderNumber($lessonIndex + 1);
                $manager->persist($lesson);
            }
        }

        // Сохранение данных в базе
        $manager->flush();
    }
}
