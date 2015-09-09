<?php

namespace Mesh\EdmMailChimpBundle\Service\Workflow;

use Mesh\EdmMailChimpBundle\Service\Api\ApiFacade;
use Mesh\EdmMailChimpBundle\Entity\EdmSyncUserQueue;
use Mesh\EdmMailChimpBundle\Model\EdmMailChimpSynchronizedInterface;
use Mesh\EncompassBundle\Entity\Site;

class SyncUserQueue
{
    /**
     *
     * @var type 
     */
    protected $entityManager;

    /**
     *
     * @var \Mesh\EdmMailChimpBundle\Service\Api\ApiFacade
     */
    protected $apiFacade;

    public function __construct($entityManager, ApiFacade $apiFacade)
    {
        $this->entityManager = $entityManager;
        $this->apiFacade = $apiFacade;
    }

    public function processSiteQueue(Site $site, $limit = 10)
    {
        $this->getLogger()->info("[SyncUserQueue:processSiteQueue] start", array('site' => $site->getId()));
        
        $queue = $this->getWaitingForSite($site, $limit);

        $this->markAsProcessing($queue);
        $this->entityManager->flush();

        $this->getLogger()->info("[SyncUserQueue:processSiteQueue] queue count: " . count($queue), array('site' => $site->getId()));

        $this->syncUsers($site, $queue);
        $this->markAsProcessed($queue);

        $this->entityManager->flush();
    }

    protected function getWaitingForSite(Site $site, $limit)
    {
        $queue = $this->entityManager
            ->getRepository('MeshEdmMailChimpBundle:EdmSyncUserQueue')
            ->getWaitingForSite($site, $limit);

        return $queue;
    }

    protected function markAsProcessing($queue)
    {
        foreach ($queue as $queueItem) {
            $queueItem->setStatus(EdmSyncUserQueue::STATUS_PROCESSING);
        }
    }

    protected function markAsProcessed($queue)
    {
        foreach ($queue as $queueItem) {
            $status = $queueItem->getUser()->getEdmMcUpdatedStatus() == EdmMailChimpSynchronizedInterface::SYNC_STATUS_UPDATED
                      ? EdmSyncUserQueue::STATUS_PROCESSED
                      : EdmSyncUserQueue::STATUS_ERROR;
            
            $queueItem->setStatus($status);
        }
    }

    protected function syncUsers(Site $site, $queue)
    {
        $users = array();

        foreach ($queue as $queueItem) {
            $users[] = $queueItem->getUser();
        }

        if (!count($users)) {
            return;
        }

        $this->apiFacade->getHighLevelApi('user')->syncPush($site->getEdmMcId(), $users);
    }

    protected function getLogger()
    {
        return $this->apiFacade->getLogger();
    }
}