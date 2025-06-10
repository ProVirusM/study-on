<?php
namespace App\Service;

use App\Dto\CourseDto;
use App\Entity\Course;
use App\Exception\BillingUnavailableException;
use App\Exception\CourseException;
use App\Exception\CourseValidationException;
use App\Exception\IsExistsCourseException;
use App\Form\CourseType;
use App\Repository\CourseRepository;
use App\Security\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class CourseService extends AbstractController
{
    public function __construct(
        private CourseRepository $courseRepository,
        private BillingClient $billingClient,
    ) {
    }

    /**
     * @throws ExceptionInterface
     * @throws CourseException
     * @throws BillingUnavailableException
     * @throws \Exception
     */
    public function getFullCourse(Course $course): ?CourseDto
    {
        $courseArray = $this->billingClient->getCourse($course->getCode());
        if (isset($courseArray['error_code'])) {
            return null;
        }
        $fullCourse = new CourseDto();
        $fullCourse->setId($course->getId())
            ->setTitle($course->getTitle())
            ->setDescription($course->getDescription())
            ->setCode($course->getCode())
            ->setType($courseArray['type'])
            ->setPrice($courseArray['price'] ?? null);
        return $fullCourse;
    }

    /**
     * @throws ExceptionInterface
     * @throws BillingUnavailableException
     * @throws \Exception
     */
    public function newCourse(CourseDto $course, User $user): bool
    {




        $existingCourse = $this->courseRepository->findOneBy(['code' => $course->getCode()]);
        if ($existingCourse !== null) {
            throw new IsExistsCourseException('Курс с таким кодом уже существует'); // Можно использовать свой IsExistsCourseException
        }

        $result = $this->billingClient->newCourse($user->getApiToken(), $course);

        $exception = $this->checkCourse($result);


        if ($exception !== null) {
            throw $exception;
        }
        $newCourse = new Course();
        $newCourse->setTitle($course->getTitle())
            ->setCode($course->getCode())
            ->setDescription($course->getDescription());
        return $this->courseRepository->persistAndFlush($newCourse);
    }

    /**
     * @throws ExceptionInterface
     * @throws BillingUnavailableException
     * @throws \Exception
     */
    public function editCourse(string $code, CourseDto $course, User $user): bool
    {

        if ($course->getType() == 'free') {
            $course->setPrice(null);
        }
        if ($course->getCode() !== null && $course->getCode() !== $code) {

            $existingCourse = $this->courseRepository->findOneBy(['code' => $course->getCode()]);
            if ($existingCourse !== null) {
                throw new IsExistsCourseException('Курс с таким кодом уже существует'); // Можно использовать свой IsExistsCourseException
            }
        }
        $result = $this->billingClient->editCourse($user->getApiToken(), $code, $course);
        $exception = $this->checkCourse($result);
        if ($exception !== null) {
            throw $exception;
        }
        $editCourse =$this->courseRepository->findOneBy(['code' => $code]);
        if ($editCourse === null) {
            return false;
        }
        $editCourse->setTitle($course->getTitle())
            ->setCode($course->getCode())
            ->setDescription($course->getDescription());
        return $this->courseRepository->persistAndFlush($editCourse);
    }

    public function checkCourse(array $result): ?\Exception
    {
        if (!isset($result['success']) || !$result['success']) {
            if (isset($result['errors'])) {
                return new CourseValidationException($result['message'], $result['code'], $result['errors']);
            }
            $message = 'Internal server error';
            if (isset($result['message'])) {
                $message = $result['message'];
            }
            return new CourseException($message);
        }
        return null;
    }

    /**
     * @throws BillingUnavailableException
     */
    public function getCoursePrice(Course $course): ?CourseDto
    {
        $courseArray = $this->billingClient->getCourse($course->getCode());
        if (isset($courseArray['error_code'])) {
            return null;
        }
        $fullCourse = new CourseDto();
        $fullCourse->setId($course->getId())
            ->setTitle($course->getTitle())
            ->setDescription($course->getDescription())
            ->setCode($course->getCode())
            ->setType($courseArray['type'])
            ->setPrice($courseArray['price'] ?? null);
        return $fullCourse;
    }

}