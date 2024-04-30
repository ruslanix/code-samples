<?php

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;

class PassageQuestionResult
{
    private Question $question;

    /**
     * @var QuestionAnswerOption[]
     */
    #[Assert\Count(
        min: 1
    )]
    private array $answerOptions;

    private bool $isCorrect;

    public function __construct(Question $question, array $answerOptions, bool $isCorrect)
    {
        $this->question = $question;
        $this->answerOptions = $answerOptions;
        $this->isCorrect = $isCorrect;
    }

    public function getQuestion(): Question
    {
        return $this->question;
    }

    public function getAnswerOptions(): array
    {
        return $this->answerOptions;
    }

    #[SerializedName('isCorrect')]
    public function isCorrect(): bool
    {
        return $this->isCorrect;
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        $userOptionIds = array_map(
            fn (QuestionAnswerOption $answerOption) => $answerOption->getId(),
            $this->answerOptions
        );
        $allowedOptionIds = array_map(
            fn (QuestionAnswerOption $answerOption) => $answerOption->getId(),
            $this->question->getAnswerOptions()
        );

        if (count(array_diff($userOptionIds, $allowedOptionIds))) {
            $context->buildViolation('Not allowed answers for given question')
                ->atPath('answerOptions')
                ->addViolation();
        }
    }
}