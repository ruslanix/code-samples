<?php

namespace App\Exception;

use Symfony\Component\Validator\ConstraintViolationListInterface;

class QuizValidationException extends QuizException
{
    public readonly ConstraintViolationListInterface $errors;

    public static function throwIfErrors(ConstraintViolationListInterface $errors, string $message): void
    {
        if (count($errors)) {
            $exception = new self($message);
            $exception->errors = $errors;
            throw $exception;
        }
    }
}