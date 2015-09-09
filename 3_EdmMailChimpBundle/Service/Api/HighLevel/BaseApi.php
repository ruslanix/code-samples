<?php

namespace Mesh\EdmMailChimpBundle\Service\Api\HighLevel;

use Mesh\EdmMailChimpBundle\Service\Api\ApiFacade;
use Mesh\EdmMailChimpBundle\Model\EdmMailChimpSynchronizedInterface;

class BaseApi
{
    /**
     *
     * @var \Mesh\EdmMailChimpBundle\Service\Api\ApiFacade
     */
    protected $apiFacade;

    protected $entityManager;

    public function __construct($entityManager, ApiFacade $apiFacade)
    {
        $this->entityManager = $entityManager;
        $this->apiFacade = $apiFacade;
    }

    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    protected function processException(\Exception $ex, EdmMailChimpSynchronizedInterface $syncEntity)
    {
        $syncEntity->addEdmMcErrorMessage(sprintf(' Catch exception : %s', $ex->getMessage()));
        $syncEntity->setEdmMcUpdatedStatus(EdmMailChimpSynchronizedInterface::SYNC_STATUS_ERROR);

        $this->logError($ex->getMessage(), $syncEntity);

        $this->entityManager->flush();
    }

    protected function logInfo($message, $entity = null)
    {
        $logContext = array(
            'source' => 'high_level_api'
        );

        if ($entity) {
            $logContext['entity_id'] = $entity->getId();
        }

        $this->getLogger()->info($message, $logContext);
    }

    protected function logError($message, $entity = null)
    {
        $logContext = array(
            'source' => 'high_level_api'
        );

        if ($entity) {
            $logContext['entity_id'] = $entity->getId();
        }

        $this->getLogger()->error($message, $logContext);
    }

    protected function getLogger()
    {
        return $this->apiFacade->getLogger();
    }
}