<?php

namespace App\Controller;

use App\Model\PassageQuestionResult;
use App\Form\Type\GetUserAnswerType;
use App\Service\PassageSession;
use App\Service\PassageResultsSaver;
use App\Service\UserAnswerEvaluator;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class QuizController extends AbstractController
{
    public function __construct(
        private PassageSession $passageSession,
        private PassageResultsSaver $passageResultsSaver,
        private UserAnswerEvaluator $answerEvaluator
    )
    {
    }

    #[Route('/', name: 'quiz_index')]
    public function index(): Response
    {
        $passage = $this->passageSession->getPassage();

        if ($passage && $passage->isCompleted()) {
            return $this->redirectToRoute('quiz_results');
        }

        if ($passage && !$passage->isCompleted()) {
            return $this->redirectToRoute('quiz_question');
        }

        return $this->render('quiz_index.html.twig');
    }

    #[Route('/start', name: 'quiz_start')]
    public function startPassage(): Response
    {
        if (!$this->passageSession->hasPassage()) {
            $this->passageSession->startNewPassage('quiz_v1');
        }

        return $this->redirectToRoute('quiz_index');
    }

    #[Route('/question', name: 'quiz_question')]
    public function question(Request $request): Response
    {
        $passage = $this->passageSession->getPassage();
        if (!$passage || $passage->isCompleted()) {
            return $this->redirectToRoute('quiz_index');
        }

        $question = $passage->getCurrentQuestion();
        if (!$question) {
            return $this->redirectToRoute('quiz_index');
        }

        $form = $this->createForm(GetUserAnswerType::class, null, [
            'question' => $question,
            'has_next' => $passage->hasNextQuestion(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // @TODO: use DTO object with form
            // Form is quite simple don't want to create DTO object for now
            // using array instead
            $userAnswerOptions = $form->getData()['answerOptions'] ?? [];
            $isCorrect = $this->answerEvaluator->isCorrect($question, $userAnswerOptions);

            $questionResult = new PassageQuestionResult($question, $userAnswerOptions, $isCorrect);
            $passage->addQuestionResult($questionResult);

            $this->passageSession->savePassage();

            if ($passage->isCompleted()) {
                $resultsEntity = $this->passageResultsSaver->savePassageResults($passage);
                $passage->setPersistedPassageResultsId($resultsEntity->getId());
                $this->passageSession->savePassage();
            }

            return $this->redirectToRoute('quiz_question');
        }

        return $this->render('quiz_question.html.twig', [
            'form' => $form,
            'count_questions' => count($passage->getQuiz()->getQuestions()),
            'count_questions_answered' => count($passage->getQuestionResults())
        ]);
    }

    #[Route('/results', name: 'quiz_results')]
    public function results(): Response
    {
        $passage = $this->passageSession->getPassage();

        if (!$passage || !$passage->isCompleted()) {
            return $this->redirectToRoute('quiz_index');
        }

        $correct = [];
        $incorrect = [];
        foreach ($passage->getQuestionResults() as $questionResult) {
            if ($questionResult->isCorrect()) {
                $correct[] = $questionResult->getQuestion();
            } else {
                $incorrect[] = $questionResult->getQuestion();
            }
        }

        return $this->render('quiz_results.html.twig', [
            'correct_questions' => $correct,
            'incorrect_questions' => $incorrect,
            'result_id' => $passage->getPersistedPassageResultsId()
        ]);
    }


    #[Route('/forget', name: 'quiz_abort')]
    public function forgetPassage(): RedirectResponse
    {
        $this->passageSession->forgetPassage();

        return $this->redirectToRoute('quiz_index');
    }

    #[Route('/exception', name: 'quiz_exception')]
    public function exception(Request $request): Response
    {
        $exception = $request->attributes->get('exception');

        if (!$exception) {
            return $this->redirectToRoute('quiz_index');
        }

        return $this->render('quiz_exception.html.twig', [
            'exception' => $exception
        ]);
    }
}