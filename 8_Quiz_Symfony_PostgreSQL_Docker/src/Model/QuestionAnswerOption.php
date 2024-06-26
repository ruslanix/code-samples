<?php

namespace App\Model;

use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

class QuestionAnswerOption
{
    #[Assert\NotBlank]
    private string $id;

    #[Assert\NotBlank]
    private string $title;

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
}