<?php

namespace App\Service;

use App\Model\Passage;
use App\Entity\PassageResults as PassageResultsEntity;
use App\Exception\QuizValidationException;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PassageResultsSaver
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PassageSerializer $passageSerializer,
        private ValidatorInterface $validator
    )
    {
    }

    public function savePassageResults(Passage $passage): PassageResultsEntity
    {
        $quizArray = $this->passageSerializer->normalizeQuizToArray($passage->getQuiz());
        $resultsArray = $this->passageSerializer->normalizePassageQuestionResultsToArray($passage);

        $entity = new PassageResultsEntity();
        $entity->setQuiz($quizArray);
        $entity->setResults($resultsArray);

        QuizValidationException::throwIfErrors(
            $this->validator->validate($entity),
            sprintf("[PassageResultsSaver] Validation failed for passage question result entity")
        );

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return $entity;
    }
}