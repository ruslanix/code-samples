<?php

namespace Application\XXXBundle\Entity;

use Application\DoctrineBundle\Model\BaseEntityRepository;

/**
 * XXXRepository
 *
 */
class XXXRepository extends BaseEntityRepository
{
    const QB_ALIAS = 'XXX_action_queue';

    public function joinEvent($qb, $eventTableAlias = 'event')
    {
        $qb->join(self::QB_ALIAS.'.event', $eventTableAlias);
    }

    public function joinAction($qb, $tableAlias = 'action')
    {
        $qb->join(self::QB_ALIAS.'.action', $tableAlias);
    }

    public function addPopulationCriteria($qb, XXXPopulation $population, $tableAlias = 'action')
    {
        $qb->andWhere($qb->expr()->eq($tableAlias.'.population', ':population_id'));
        $qb->setParameter('population_id', $population->getId());
    }

    public function addBeforeEventCriteria($qb, XXXEvent $event, $tableAlias = 'event')
    {
        $qb->andWhere($qb->expr()->lt($tableAlias.'.id', ':event_id'));
        $qb->setParameter('event_id', $event->getId());
    }

    public function addStatusCriteria($qb, $statusAliases)
    {
        $qb->andWhere($qb->expr()->in(self::QB_ALIAS.'.status', ':status_aliases'));
        $qb->setParameter('status_aliases', $statusAliases);
    }

    /**
     * Get ready for execution queue items
     * . status = pending
     * . execute_at <= now
     *
     * @param integer $limit
     * @param bool $returnQueryBuilder
     * @return QueryBuilder|array
     */
    public function getReadyForExecution($limit = null, $returnQueryBuilder = false)
    {
        $qb = $this->getQueryBuilder();

        $qb
            ->where($qb->expr()->eq(self::QB_ALIAS.'.status', ":pending"))
            ->andwhere(self::QB_ALIAS.".execute_at <= :date_now")
            ->setParameters(array(
                'pending' => XXXStatusType::PENDING,
                'date_now' => new \DateTime('now')
            ));

        if(!is_null($limit)){
            $qb->setMaxResults($limit);
        }

        if($returnQueryBuilder){
            return $qb;
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns the actions preceding the given event and have the specified status.
     * 
     * @param \Application\XXXBundle\Entity\XXXEvent $event
     * @param array $statuses
     * @param int $limit
     * @param bool $returnQueryBuilder
     * @return array
     */
    public function getBeforeEventAndInStatus(XXXEvent $event, $statuses, $limit = null, $returnQueryBuilder = false)
    {
        $qb = $this->getQueryBuilder();

        $this->joinEvent($qb);
        $this->addBeforeEventCriteria($qb, $event);
        $this->addPopulationCriteria($qb, $event->getPopulation(), 'event');
        $this->addStatusCriteria($qb, $statuses);

        if(!is_null($limit)){
            $qb->setMaxResults($limit);
        }

        if($returnQueryBuilder){
            return $qb;
        }

        return $qb->getQuery()->getResult();
    }

    public function getBeforeEventAndInStatusCount(XXXEvent $event, $statuses, $returnQueryBuilder = false)
    {
        $qb = $this->getBeforeEventAndInStatus($event, $statuses, null, true);
        $qb->select("COUNT(".self::QB_ALIAS.".id)");

        if($returnQueryBuilder){
            return $qb;
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}