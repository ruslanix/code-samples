<?php

namespace App\Serializer;

use App\Model\Quiz;
use App\Model\Question;
use App\Model\QuestionAnswerOption;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class QuizModelsReferencesDenormalizer implements DenormalizerInterface
{
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        /** @var Quiz $quiz */
        $quiz = $context[QuizAwareContextBuilder::CONTEXT_KEY];

        if (is_a($type, Quiz::class, true) && $data === $quiz->getId()) {
            return $quiz;
        }

        if (is_a($type, Question::class, true)) {
            return $quiz->findQuestionById((string) $data);
        }

        if (is_a($type, QuestionAnswerOption::class, true)) {
            return $quiz->findQuestionAnswerOptionById((string) $data);
        }

        return null;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
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