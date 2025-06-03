<?php

namespace App\Exception;

class CourseValidationException extends \Exception
{
    public function __construct(
        string $message = 'Ошибка валидации',
        int $code = 400,
        public array $errors = [],
        \Exception $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}