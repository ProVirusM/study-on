<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Exception\BillingUnavailableException;
use App\Form\LessonType;
use App\Repository\LessonRepository;
use App\Service\BillingClient;
use App\Service\CourseService;
use App\Service\PurchasedCourses;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/lesson')]
final class LessonController extends AbstractController
{
    public function __construct(
        private PurchasedCourses $purchasedCourses,
        private CourseService $courseService,
        private BillingClient $billingClient,
    ) {
    }

    #[Route(name: 'app_lesson_index', methods: ['GET'])]
    public function index(LessonRepository $lessonRepository): Response
    {
        return $this->render('lesson/index.html.twig', [
            'lessons' => $lessonRepository->findAll(),
        ]);
    }

    #[Route('/new/{course}', name: 'app_lesson_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, Course $course): Response
    {
        $lesson = new Lesson();
        $lesson->setCourse($course);

        $form = $this->createForm(LessonType::class, $lesson, ['course' => $course, 'require_course' => true,]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($lesson);
            $entityManager->flush();

            return $this->redirectToRoute('app_course_show', ['id' => $course->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('lesson/new.html.twig', [
            'lesson' => $lesson,
            'course' => $course,
            'form' => $form,
        ]);
    }

    /**
     * @throws BillingUnavailableException
     */
    #[Route('/{id}', name: 'app_lesson_show', methods: ['GET'])]
    public function show(Lesson $lesson): Response
    {
        //$user = $this->getUser();
        $course = $lesson->getCourse();
        $course = $this->purchasedCourses->getDataCourse($course);
        if (
            $course['type'] !== 'free'
            && empty($course['isPurchased'])
            && !$this->isGranted('ROLE_SUPER_ADMIN')
        ) {
            throw new AccessDeniedException('Для доступа к этому уроку необходимо приобрести курс.');
        }
        return $this->render('lesson/show.html.twig', [
            'lesson' => $lesson,
            'course' => $lesson->getCourse(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_lesson_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Lesson $lesson, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LessonType::class, $lesson);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_lesson_show', ['id' => $lesson->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('lesson/edit.html.twig', [
            'lesson' => $lesson,
            'course' => $lesson->getCourse(),
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_lesson_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Lesson $lesson,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$lesson->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($lesson);
            $entityManager->flush();
        }

        return $this->redirectToRoute(
            'app_course_show',
            ['id' => $lesson->getCourse()->getId()],
            Response::HTTP_SEE_OTHER
        );
    }
}
