<?php

namespace App\Controller;

use App\Dto\CourseDto;
use App\Entity\Course;
use App\Exception\BillingUnavailableException;
use App\Exception\CourseException;
use App\Exception\CourseValidationException;
use App\Exception\IsExistsCourseException;
use App\Form\CourseType;
use App\Repository\CourseRepository;
use App\Service\BillingClient;
use App\Service\CourseService;
use App\Service\PurchasedCourses;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

#[Route('/courses')]
final class CourseController extends AbstractController
{

    public function __construct(
        private PurchasedCourses $purchasedCourses,
        private CourseService $courseService,
        private BillingClient $billingClient,
    ) {
    }
    #[Route(name: 'app_course_index', methods: ['GET'])]
    public function index(CourseRepository $courseRepository): Response
    {
        $coursesData = $courseRepository->findAll();
        $courses = [];
        foreach ($coursesData as $courseData) {
            $courses[] = $this->purchasedCourses->getDataCourse($courseData);
        }

        return $this->render('course/index.html.twig', [
            'courses' => $courses,
        ]);
    }

    #[Route('/new', name: 'app_course_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $course = new CourseDto();
        $user = $this->getUser();
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $user !== null) {
            $result = false; // Инициализируем переменную

            try {
                $result = $this->courseService->newCourse($course, $user);
                if (!$result) {
                    $form->addError(new FormError('Ошибка добавления курса'));
                }
            } catch (\Exception $exception) {
                $this->processException($exception, $form);
            } catch (ExceptionInterface $e) {
                // Обрабатываем ошибки биллинга
                $form->addError(new FormError($e->getMessage()));
            }

            if (!$result) {
                return $this->render('course/new.html.twig', [
                    'course' => $course,
                    'form' => $form,
                ]);
            }

            return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('course/new.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_course_show', methods: ['GET'])]
    public function show(Course $course): Response
    {
        $user = $this->getUser();
//        dd($user);
//        if (!$user) {
//            throw $this->createAccessDeniedException();
//        }
        if ($user !== null) {
            $userBalance = $this->billingClient->getCurrentUser($user->getApiToken())['balance'];
        } else {
            $userBalance = 0;
        }

        $lessons = $course->getLessons();
        $course = $this->purchasedCourses->getDataCourse($course);

        return $this->render('course/show.html.twig', [
            'course' => $course,
            'user_balance' => $userBalance,
            'lessons' => $lessons,
        ]);
    }

    /**
     * @throws ExceptionInterface
     * @throws BillingUnavailableException
     * @throws CourseException
     */
    #[Route('/{id}/edit', name: 'app_course_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Course $course, Course $courseEntity, EntityManagerInterface $entityManager): Response
    {
        $course = $this->courseService->getFullCourse($courseEntity);
        $code = $courseEntity->getCode();

        $user = $this->getUser();
        if ($course === null) {
            return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
        }
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $result = $this->courseService->editCourse($code, $course, $user);
                if (!$result) {
                    $form->addError(new FormError('Ошибка изменения курса'));
                }
            } catch (\Exception $exception) {
                $this->processException($exception, $form);
                $result = false;
            }
            if (!$result) {
                return $this->render('course/edit.html.twig', [
                    'course' => $course,
                    'form' => $form,
                ]);
            }

            return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('course/edit.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }


    #[Route('/{id}', name: 'app_course_delete', methods: ['POST'])]
    public function delete(Request $request, Course $course, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$course->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($course);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
    }


    #[Route('/{id}/pay', name: 'app_course_pay')]
    public function pay(Course $course): Response
    {

        $user = $this->getUser();

        try {
            $billingResponse = $this->billingClient->payCourse($course->getCode(),$user->getApiToken());

            // Улучшенная обработка ответа
            if (!is_array($billingResponse)) {
                throw new \RuntimeException('Invalid response from billing service');
            }

            if (isset($billingResponse['success']) && $billingResponse['success']) {
                $message = $billingResponse['message'] ?? 'Оплата прошла успешно';
                $this->addFlash('success', $message);
            } elseif (isset($billingResponse['error_code'])) {
                $message = $billingResponse['message'] ?? 'Произошла ошибка при оплате';
                $this->addFlash('error', $message);
            } else {
                $this->addFlash('error', 'Неизвестный ответ от сервиса оплаты');
            }

        } catch (\Exception $e) {
            $this->addFlash('error', 'Ошибка при обработке платежа: ' . $e->getMessage());
        }


        return $this->redirectToRoute('app_course_show', ['id' => $course->getId()], Response::HTTP_SEE_OTHER);
    }
    private function processException(\Exception $exception, FormInterface $form): void
    {
        if ($exception instanceof CourseValidationException) {
            foreach ($exception->errors as $error) {
                $form->get($error['property'])->addError(new FormError($error['message']));
            }
            return;
        }
        if ($exception instanceof IsExistsCourseException) {
            if ($form->has('code')) {
                $form->get('code')->addError(new FormError($exception->getMessage()));
                return;
            }
        }
        $form->addError(new FormError($exception->getMessage()));
    }
}