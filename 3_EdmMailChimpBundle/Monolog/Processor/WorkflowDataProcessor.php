<?php

namespace Mesh\EdmMailChimpBundle\Monolog\Processor;

use Symfony\Component\DependencyInjection\ContainerInterface;

class WorkflowDataProcessor
{
    /**
     *
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    public function  __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    public function processRecord(array $record)
    {
        $context = $this->container->get('mesh.edm_mailchimp.api.context');
        
        try {
            $record['extra']['workflow_hash'] = $context->getWorkflowHash();
            $record['extra']['workflow_name'] = $context->getWorkflowName();
        } catch (\Exception $e) {
            $record['extra']['workflow_hash'] = 'unknown';
            $record['extra']['workflow_name'] = 'unknown';
        }

        return $record;
    }
}