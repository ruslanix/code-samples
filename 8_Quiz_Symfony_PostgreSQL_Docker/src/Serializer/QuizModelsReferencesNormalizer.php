<?php

namespace App\Serializer;

use App\Model\Quiz;
use App\Model\Question;
use App\Model\QuestionAnswerOption;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class QuizModelsReferencesNormalizer implements NormalizerInterface
{
    public function normalize($quizModel, ?string $format = null, array $context = []): string
    {
        return $quizModel->getId();
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        $quiz = $context[QuizAwareContextBuilder::CONTEXT_KEY] ?? null;

        return $quiz instanceof Quiz;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Quiz::class => true,
            Question::class => true,
            QuestionAnswerOption::class => true,
        ];
    }
}