<?php
namespace xxx;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

use xxx;

class QueueOrmListener
{
    protected $priceDeterminant;

    public function __construct(PriceDeterminant $priceDeterminant)
    {
        $this->priceDeterminant = $priceDeterminant;
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if ($entity instanceof Queue)
        {
            $entity->setCustomerPrice(
                $this->priceDeterminant->getCustomerPriceByQueue($entity)
            );
        }
    }

    public function preUpdate(PreUpdateEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();
        $entity = $args->getEntity();

        if ($entity instanceof Queue)
        {
            $changeset = $uow->getEntityChangeSet($entity);
            if(isset($changeset['price']) && ($changeset['price'][0] != $changeset['price'][1]))
            {
                $entity->setCustomerPrice(
                    $this->priceDeterminant->getCustomerPriceByQueue($entity)
                );

                $uow->recomputeSingleEntityChangeSet(
                    $em->getClassMetadata("xxx:xxx"),
                    $entity
                );
            }
        }
    }
}