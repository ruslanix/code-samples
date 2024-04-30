<?php

namespace App\Exception;

class QuizException extends \Exception
{
    public readonly ?\Exception $wrappedException;

    public static function fromException(\Exception $e, string $message)
    {
        $exception = new self($message);
        $exception->wrappedException = $e;

        return $exception;
    }
}