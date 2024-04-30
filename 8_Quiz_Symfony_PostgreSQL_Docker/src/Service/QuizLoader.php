<?php

namespace App\Service;

use App\Model\Quiz;
use App\Exception\QuizException;
use App\Exception\QuizValidationException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class QuizLoader
{
    public function __construct(
        private PassageSerializer $passageSerializer,
        private ValidatorInterface $validator
    )
    {
    }

    public function loadQuiz(string $id): Quiz
    {
        // @TODO: locate and load quiz from some file or DB
        // super hardcode for now

        if ($id !== 'quiz_v1') {
           throw new QuizException("We currently support only `quiz_v1` version");
        }

        $quiz = $this->passageSerializer->deserializeQuiz($this->getQuizJson());

        if (!$quiz instanceof Quiz) {
            throw new QuizException("[QuizLoader] Can't load quiz from json");
        }

        QuizValidationException::throwIfErrors(
            $this->validator->validate($quiz),
            sprintf("[QuizLoader] Quiz `%s` configuration is not valid.", $id)
        );

        return $quiz;
    }

    private function getQuizJson(): string
    {
        return
<<<'EOF'
{
    "id": "quiz_v1",
    "questions": [
      {
        "title": "1+1 =",
        "answerOptions": [
          {
            "title": "3"
          },
          {
            "title": "2"
          },
          {
            "title": "0"
          }
        ]
      },
      {
        "title": "2+2 =",
        "answerOptions": [
          {
            "title": "4"
          },
          {
            "title": "3+1"
          },
          {
            "title": "10"
          }
        ]
      },
      {
        "title": "3+3 =",
        "answerOptions": [
          {
            "title": "1+5"
          },
          {
            "title": "1"
          },
          {
            "title": "6"
          },
          {
            "title": "2+4"
          }
        ]
      },
      {
        "title": "4+4=",
        "answerOptions": [
          {
            "title": "8"
          },
          {
            "title": "4"
          },
          {
            "title": "0"
          },
          {
            "title": "0+8"
          }
        ]
      },
      {
        "title": "5+5=",
        "answerOptions": [
          {
            "title": "6"
          },
          {
            "title": "18"
          },
          {
            "title": "10"
          },
          {
            "title": "9"
          },
          {
            "title": "0"
          }
        ]
      },
      {
        "title": "6+6=",
        "answerOptions": [
          {
            "title": "3"
          },
          {
            "title": "0"
          },
          {
            "title": "12"
          },
          {
            "title": "5+7"
          }
        ]
      },
      {
        "title": "7+7=",
        "answerOptions": [
          {
            "title": "5"
          },
          {
            "title": "14"
          }
        ]
      },
      {
        "title": "8+8=",
        "answerOptions": [
          {
            "title": "16"
          },
          {
            "title": "12"
          },
          {
            "title": "9"
          },
          {
            "title": "5"
          }
        ]
      }
    ]
  }
EOF;
    }
}