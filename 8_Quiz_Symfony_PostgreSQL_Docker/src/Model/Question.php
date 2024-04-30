<?php

namespace App\Model;

use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

class Question
{
    #[Assert\NotBlank]
    private string $id;

    #[Assert\NotBlank]
    private string $title;

    /**
     * @var QuestionAnswerOption[]
     */
    #[Assert\Count(
        min: 1
    )]
    #[Assert\Valid]
    private array $answerOptions = [];

    public function __construct(?string $id, string $title)
    {
        $this->id = $id ?: Uuid::v1();
        $this->title = $title;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return QuestionAnswerOption[]
     */
    public function getAnswerOptions(): array
    {
        return $this->answerOptions;
    }

    public function setAnswerOptions(array $answerOptions): self
    {
        $this->answerOptions = $answerOptions;

        return $this;
    }

    public function findAnswerOptionById($optionId): ?QuestionAnswerOption
    {
        foreach ($this->answerOptions as $option) {
            if ($option->getId() === $optionId) {
                return $option;
            }
        }

        return null;
    }

    public function shuffle(): self
    {
        shuffle($this->answerOptions);

        return $this;
    }
}