<?php
namespace App\Validator;

use App\Repository\CourseRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueCourseCodeValidator extends ConstraintValidator
{
    private CourseRepository $courseRepository;

    public function __construct(CourseRepository $courseRepository)
    {
        $this->courseRepository = $courseRepository;
    }

    public function validate($value, Constraint $constraint): void
    {
        if (null === $value || '' === $value) {
            return;
        }

        $existingCourse = $this->courseRepository->findOneBy(['code' => $value]);

        if ($existingCourse) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}