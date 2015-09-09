<?php

namespace Application\XXXBundle\Service\EventHandler;

use Doctrine\ORM\EntityManager;

use XXX;

/**
 * Event handler for patient_passed_xxx eventType
 * Filter user and create action_queue
 */
class PatientPassedxxxHandler implements EventHandlerInterface
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var Application\XXXBundle\Service\Serialization\XXXSerialization
     */
    protected $serializer;

    /**
     * @var Application\XXXBundle\Service\Rule\RuleEvaluation
     */
    protected $evaluator;

    public function __construct(EntityManager $entityManager,
                                XXXSerialization $serializer,
                                RuleEvaluation $evaluator,
                                ActionQueueGenerator $actionQueueGenerator)
    {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->evaluator = $evaluator;
        $this->actionQueueGenerator = $actionQueueGenerator;
    }

    public function onEvent($event)
    {
        $xxxPassage = $this->getxxxPassage($event);

        if (!$xxxPassage) {
            $this->finishEvent($event, 'xxx');
            return;
        }

        $patient = $xxxPassage->getOrganizationPatient();

        if (!$patient) {
            $this->finishEvent($event, 'xxx');
            return;
        }

        if (!$this->isScoreCalculated($xxxPassage)) {
            $event->addProcessMessage('xxx');
            $this->entityManager->flush();
            return;
        }

        $message = 'xxx';

        if ($this->filterPatient($event, $patient)) {
            $this->generateActionQueue($event, $patient);
            $message = 'xxx';
        }

        $this->finishEvent($event, $message);
    }

    protected function finishEvent($event, $message = null)
    {
        $event->setStatus(XXXEventStatusType::SUCCESS);
        $event->setProcessMessage($message);
        $this->entityManager->flush();
    }

    protected function getxxxPassage(XXXEventPatientPassedxxx $event)
    {
        $xxxPassageId = $event->getxxxPassageId();

        if (!$xxxPassageId) {
            throw new \Exception('xxx');
        }

        return $this->entityManager->getRepository('ApplicationxxxBundle:xxxPassage')
            ->findOneById($xxxPassageId);
    }

    protected function filterPatient($event, OrganizationPatient $patient)
    {
        $rule = unserialize(
            $event->getPopulation()->getFilter()->getData()
        );

        return $this->evaluator->evaluatePatientRule($rule, $patient);
    }

    protected function generateActionQueue($event, OrganizationPatient $patient)
    {
        $this->actionQueueGenerator->generatePatientActionQueueByEvent($event, $patient, false);
    }

    protected function isScoreCalculated(xxxPassage $xxxPassage)
    {
        if ($xxxPassage->getSurvey()->getScoresIsEnabled()->count()) {

            $cache = $this->entityManager->getRepository('ApplicationxxxBundle:xxx')
                ->findBy(array('xxx_passage' => $xxxPassage));

            return (bool)($cache && count($cache));
        }

        return true;
    }
}
