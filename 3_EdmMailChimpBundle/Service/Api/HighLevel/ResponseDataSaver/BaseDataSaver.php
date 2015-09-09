<?php

namespace Mesh\EdmMailChimpBundle\Service\Api\HighLevel\ResponseDataSaver;

use Mesh\EdmMailChimpBundle\Model\EdmMailChimpSynchronizedInterface;
use Mesh\EdmMailChimpBundle\Service\Api\LowLevel\MailChimpResponse;

abstract class BaseDataSaver
{
    protected $entityManager;

    public function __construct($entityManager)
    {
        $this->entityManager = $entityManager;
    }

    protected function validateResponse(MailChimpResponse $mailChimpResponse)
    {
    }

    protected function getEntityManager()
    {
        return $this->entityManager;
    }
}