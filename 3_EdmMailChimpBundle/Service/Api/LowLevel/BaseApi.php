<?php

namespace Mesh\EdmMailChimpBundle\Service\Api\LowLevel;

use Hype\MailchimpBundle\Mailchimp\MailChimp as HypeMailChimp;

use Mesh\EdmMailChimpBundle\HypeMailChimpV3\HypeMailChimpV3;
use Mesh\EdmMailChimpBundle\Service\Api\ApiFacade;

abstract class BaseApi
{
    /**
     *
     * @var \Mesh\EdmMailChimpBundle\Service\Api\ApiFacade
     */
    protected $apiFacade;
    
    /**
     *
     * @var Hype\MailchimpBundle\Mailchimp\MailChimp 
     */
    protected $hypeMailChimp;

    /**
     *
     * @var \Mesh\EdmMailChimpBundle\HypeMailChimpV3\HypeMailChimpV3
     */
    protected $hypeMailChimpV3;

    public function __construct(HypeMailChimp $hypeMailChimp, HypeMailChimpV3 $hypeMailChimpV3, ApiFacade $apiFacade)
    {
        $this->hypeMailChimp = $hypeMailChimp;
        $this->hypeMailChimpV3 = $hypeMailChimpV3;
        $this->apiFacade = $apiFacade;
    }

    protected function logInfo($message, $entityId = null, $data = null)
    {
        if (!$data) {
            $data = array();
        }
        $data['source'] = 'low_level_api';
        if ($entityId) {
            $data['entity_id'] = $entityId;
        }

        $this->getLogger()->info($message, $data);
    }

    protected function logError($message, $entityId = null, $data = null)
    {
        if (!$data) {
            $data = array();
        }
        $data['source'] = 'low_level_api';
        if ($entityId) {
            $data['entity_id'] = $entityId;
        }

        $this->getLogger()->error($message, $data);
    }

    protected function getLogger()
    {
        return $this->apiFacade->getLogger();
    }
}