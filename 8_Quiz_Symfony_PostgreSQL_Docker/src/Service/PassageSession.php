<?php

namespace App\Service;

use App\Model\Passage;
use App\Model\Quiz;
use App\Exception\QuizException;
use App\Exception\QuizValidationException;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PassageSession
{
    private const SESSION_KEY_QUIZ = 'current-quiz';
    private const SESSION_KEY_PASSAGE = 'current-passage';

    private ?Passage $passage = null;
    private bool $isSessionLoaded = false;

    public function __construct(
        private RequestStack $requestStack,
        private PassageSerializer $passageSerializer,
        private QuizLoader $quizLoader,
        private ValidatorInterface $validator
    )
    {
    }

    public function hasPassage(): bool
    {
        $this->loadPassageSession();

        return $this->passage !== null;
    }

    public function getPassage(): ?Passage
    {
        $this->loadPassageSession();

        return $this->passage;
    }

    public function startNewPassage(string $quizId): Passage
    {
        $this->forgetPassage();

        $quiz = $this->quizLoader->loadQuiz($quizId);
        $quiz->shuffle();
        $passage = new Passage($quiz);
        $this->doSavePassageSession($passage);
        $passage = $this->doLoadPassageSession();

        if (!$passage) {
            throw new QuizException("Can't start new quiz passage session");
        }

        $this->passage = $passage;

        return $this->passage;
    }

    public function savePassage(): void
    {
        $this->loadPassageSession();

        if ($this->passage) {
            $this->doSavePassageSession($this->passage);
        }
    }

    public function forgetPassage(): void
    {
        $this->doClearPassageSession();
        $this->passage = null;
        $this->isSessionLoaded = false;
    }

    private function loadPassageSession()
    {
        if (!$this->isSessionLoaded) {
            $this->passage = $this->doLoadPassageSession();
            $this->isSessionLoaded = true;
        }
    }

    private function doClearPassageSession(): void
    {
        $this->requestStack->getSession()->remove(self::SESSION_KEY_QUIZ);
        $this->requestStack->getSession()->remove(self::SESSION_KEY_PASSAGE);
    }

    private function doLoadPassageSession(): ?Passage
    {
        $quizJson = $this->requestStack->getSession()->get(self::SESSION_KEY_QUIZ, null);
        if ($quizJson === null) {
            return null;
        }

        $passageJson = $this->requestStack->getSession()->get(self::SESSION_KEY_PASSAGE, null);
        if ($passageJson === null) {
            return null;
        }

        $quiz = $this->passageSerializer->deserializeQuiz($quizJson);

        if (!$quiz instanceof Quiz) {
            $this->doClearPassageSession();

            return null;
        }

        $passage = $this->passageSerializer->deserializePassage($quiz, $passageJson);
        if (!$passage instanceof Passage) {
            $this->doClearPassageSession();

            return null;
        }

        QuizValidationException::throwIfErrors(
            $this->validator->validate($passage),
            sprintf("[PassageSession] Validation failed for loaded passage")
        );

        return $passage;
    }

    private function doSavePassageSession(Passage $passage): void
    {
        QuizValidationException::throwIfErrors(
            $this->validator->validate($passage),
            sprintf("[PassageSession] Validation failed for passage")
        );

        $quizJson = $this->passageSerializer->serializeQuiz($passage->getQuiz());
        $passageJson = $this->passageSerializer->serializePassage($passage);

        $this->requestStack->getSession()->set(self::SESSION_KEY_QUIZ, $quizJson);
        $this->requestStack->getSession()->set(self::SESSION_KEY_PASSAGE, $passageJson);
    }
}