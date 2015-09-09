<?php
namespace Mesh\EdmMailChimpBundle\Event\Listener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Mesh\EncompassBundle\Entity\User;
use Mesh\EdmMailChimpBundle\Entity\EdmSyncUserQueue;

class UserListener
{
    protected $insertUsers;
    
    /** @ORM\onFlush */
    public function onFlush(OnFlushEventArgs $event)
    {
        $this->insertUsers = array();

        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $event->getEntityManager();
        /* @var $uow \Doctrine\ORM\UnitOfWork */
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if (
                    $entity instanceof User
                    && $entity->isEdmParticipant()
                    && $entity->getSite()
                    && $entity->getSite()->getEdmMcId()
            ) {
                $this->insertUsers[] = $entity;
            }
        }
    }

    /** @ORM\postFlush */
    public function postFlush(PostFlushEventArgs $event)
    {
        if (empty($this->insertUsers)) {
            return;
        }

        $em = $event->getEntityManager();

        foreach ($this->insertUsers as $user) {
            
            // TODO:EDM: what todo ?
            if (!$user->getSite()) {
                continue;
            }

            $syncQueue = new EdmSyncUserQueue();
            $syncQueue->setUser($user);
            $syncQueue->setSite($user->getSite());

            $em->persist($syncQueue);
        }

        $em->flush();

        $this->insertUsers = array();
    }
}
