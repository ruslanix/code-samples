<?php

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;

class Quiz
{
    #[Assert\NotBlank]
    private string $id;

    /**
     * @var Question[]
     */
    #[Assert\Count(
        min: 1
    )]
    #[Assert\Valid]
    private array $questions = [];

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return Question[]
     */
    public function getQuestions(): array
    {
        return $this->questions;
    }

    public function setQuestions(array $questions)
    {
        $this->questions = $questions;
    }

    public function findQuestionById(string $questionId): ?Question
    {
        foreach ($this->questions as $question) {
            if ($question->getId() === $questionId) {
                return $question;
            }
        }

        return null;
    }

    public function findQuestionAnswerOptionById(string $answerOptionId): ?QuestionAnswerOption
    {
        foreach ($this->questions as $question) {
            if ($answerOption = $question->findAnswerOptionById($answerOptionId)) {
                return $answerOption;
            }
        }

        return null;
    }

    public function shuffle(): self
    {
        shuffle($this->questions);
        foreach ($this->questions as $question) {
            $question->shuffle();
        }

        return $this;
    }
}