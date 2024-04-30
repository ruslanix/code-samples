<?php

namespace App\Form\Type;

use App\Model\Question;
use App\Model\QuestionAnswerOption;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\EqualTo;
use Symfony\Component\Validator\Constraints\Count;

class GetUserAnswerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Question $question */
        $question = $options['question'];

        $builder
            ->add('question_id', HiddenType::class, [
                'data' => $question->getId(),

                'constraints' => [
                    new EqualTo(value: $question->getId(), message: "Something wrong with current question order. Try to reload the page.")
                ],
            ])
            ->add('answerOptions', ChoiceType::class, [
                'choices'  => $question->getAnswerOptions(),
                'choice_value' => 'id',
                'choice_label' => fn (?QuestionAnswerOption $answerOption) => $answerOption?->getTitle(),

                'expanded' => true,
                'multiple' => true,

                'label' => $question->getTitle(),

                'constraints' => [
                    new Count(min: 1, minMessage: "Please make some choice")
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => $options['has_next'] ? 'Next' : 'Finish'
            ])
        ;

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined('question')
            ->setAllowedTypes('question', Question::class)
            ->setRequired('question');

        $resolver
            ->setDefined('has_next')
            ->setAllowedTypes('has_next', 'bool')
            ->setRequired('has_next');
    }
}