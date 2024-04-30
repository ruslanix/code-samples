<?php

namespace App\Service;

use App\Model\Passage;
use App\Model\Quiz;
use App\Serializer\QuizAwareContextBuilder;

use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class PassageSerializer
{
    public function __construct(
        private SerializerInterface $serializer,
        private NormalizerInterface $normalizer
    )
    {
    }

    public function serializeQuiz(Quiz $quiz)
    {
        return $this->serializer->serialize($quiz, 'json');
    }

    public function deserializeQuiz(string $quizJson): ?Quiz
    {
        /** @var Quiz */
        $quiz = $this->serializer->deserialize($quizJson, Quiz::class, 'json');

        return $quiz instanceof Quiz ? $quiz : null;
    }

    public function serializePassage(Passage $passage): string
    {
        $context = (new QuizAwareContextBuilder())
          ->withQuiz($passage->getQuiz())
          ->toArray();

        return $this->serializer->serialize($passage, 'json', $context);
    }

    public function deserializePassage(Quiz $quiz, string $passageJson): ?Passage
    {
        $context = (new QuizAwareContextBuilder())
          ->withQuiz($quiz)
          ->toArray();

        $passage = $this->serializer->deserialize($passageJson, Passage::class, 'json', $context);

        return $passage instanceof Passage ? $passage : null;
    }

    public function normalizeQuizToArray(Quiz $quiz): array
    {
        return $this->normalizer->normalize($quiz, 'array');
    }

    public function normalizePassageQuestionResultsToArray(Passage $passage): array
    {
        $context = (new QuizAwareContextBuilder())
          ->withQuiz($passage->getQuiz())
          ->toArray();

        return $this->normalizer->normalize($passage->getQuestionResults(), 'array', $context);
    }
}