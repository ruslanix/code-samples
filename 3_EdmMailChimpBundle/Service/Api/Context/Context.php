<?php

namespace Mesh\EdmMailChimpBundle\Service\Api\Context;

use Mesh\EdmMailChimpBundle\Service\Api\ApiFacade;

class Context
{
    /**
     *
     * @var \Mesh\EdmMailChimpBundle\Service\Api\ApiFacade
     */
    protected $apiFacade;

    protected $workflowName;
    protected $workflowHash;

    public function  __construct(ApiFacade $apiFacade)
    {
        $this->apiFacade = $apiFacade;
    }

    public function startWorkflow($name = 'unknown')
    {
        $this->workflowName = $name;
        $this->workflowHash = substr(md5(uniqid(mt_rand(1, 1000))), 0, 10);

        $this->getLogger()->info("Start workflow: {$this->workflowName}/{$this->workflowHash}", array('source' => 'Context'));
    }

    public function getWorkflowHash()
    {
        if (!$this->workflowHash) {
            $this->startWorkflow();
        }

        return $this->workflowHash;
    }

    public function getWorkflowName()
    {
        if (!$this->workflowHash) {
            $this->startWorkflow();
        }

        return $this->workflowName;
    }

    protected function getLogger()
    {
        return $this->apiFacade->getLogger();
    }
}