<?php

namespace App\Serializer;

use App\Model\Quiz;
use Symfony\Component\Serializer\Context\ContextBuilderInterface;
use Symfony\Component\Serializer\Context\ContextBuilderTrait;

final class QuizAwareContextBuilder implements ContextBuilderInterface
{
    use ContextBuilderTrait;

    public const CONTEXT_KEY = 'quiz';

    public function withQuiz(Quiz $quiz): static
    {
        return $this->with(QuizAwareContextBuilder::CONTEXT_KEY, $quiz);
    }
}