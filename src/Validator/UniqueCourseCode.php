<?php
namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
#[\Attribute] class UniqueCourseCode extends Constraint
{
    public string $message = 'Курс с таким кодом уже существует';
}