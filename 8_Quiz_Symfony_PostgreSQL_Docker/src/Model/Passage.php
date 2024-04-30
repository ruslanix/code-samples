<?php

namespace App\Model;

use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

class Passage
{
    #[Assert\Valid]
    private Quiz $quiz;

    /**
     * @var PassageQuestionResult[]
     */
    #[Assert\Valid]
    private array $questionResults = [];

    private ?string $persistedPassageResultsId = null;

    public function __construct(Quiz $quiz)
    {
        $this->quiz = $quiz;
    }

    public function getQuiz(): Quiz
    {
        return $this->quiz;
    }

    public function getPersistedPassageResultsId(): ?string
    {
        return $this->persistedPassageResultsId;
    }

    public function setPersistedPassageResultsId(?string $id): self
    {
        $this->persistedPassageResultsId = $id;

        return $this;
    }

    /**
     * @return PassageQuestionResult[]
     */
    public function getQuestionResults(): array
    {
        return $this->questionResults;
    }

    public function setQuestionResults(array $questionResults): self
    {
        $this->questionResults = $questionResults;

        return $this;
    }

    public function addQuestionResult(PassageQuestionResult $questionResult): self
    {
        // question can have only one result
        if ($this->findQuestionResultById($questionResult->getQuestion()->getId())) {
            return $this;
        }

        $this->questionResults[] = $questionResult;

        return $this;
    }

    public function findQuestionResultById(string $questionId): ?PassageQuestionResult
    {
        foreach ($this->questionResults as $result) {
            if ($result->getQuestion()->getId() === $questionId) {
                return $result;
            }
        }

        return null;
    }

    #[Ignore]
    public function getCurrentQuestion(): ?Question
    {
        $answeredQuestionIds = array_map(fn(PassageQuestionResult $res) => $res->getQuestion()->getId(),  $this->questionResults);
        foreach ($this->quiz->getQuestions() as $question) {
            if (!in_array($question->getId(), $answeredQuestionIds)) {
                return $question;
            }
        }

        return null;
    }

    #[Ignore]
    public function hasNextQuestion(): bool
    {
        return count($this->quiz->getQuestions()) > count($this->questionResults) + 1;
    }

    #[Ignore]
    public function isCompleted(): bool
    {
        return count($this->quiz->getQuestions()) === count($this->questionResults);
    }
}