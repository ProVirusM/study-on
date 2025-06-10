<?php
namespace App\Exception;

class IsExistsCourseException extends \Exception
{
    public $message = 'Курс с таким кодом уже существует';
}