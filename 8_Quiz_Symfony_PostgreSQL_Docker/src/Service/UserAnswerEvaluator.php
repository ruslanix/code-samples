<?php

namespace App\Service;

use App\Model\Question;
use App\Model\QuestionAnswerOption;
use App\Exception\QuizException;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class UserAnswerEvaluator
{
    /**
     * @param QuestionAnswerOption[] $userAnswerOptions
     */
    public function isCorrect(Question $question, array $userAnswerOptions): bool
    {
        $expression = new ExpressionLanguage();

        $isCorrect = count($userAnswerOptions) !== 0;

        $_question = trim($question->getTitle(), "="); // small hack :)

        try {
            foreach ($userAnswerOptions as $answerOption) {
                $isCorrect = $isCorrect &&  $expression->evaluate(sprintf('%s === %s', $_question, $answerOption->getTitle()));

                if (!$isCorrect) {
                    return false;
                }
            }
        } catch (\Exception $e) {
            throw QuizException::fromException($e, "Can't evaluate answers");
        }

        return $isCorrect;
    }
}