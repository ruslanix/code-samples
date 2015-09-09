<?php

namespace Mesh\EdmMailChimpBundle\Service\Api;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

/**
 * ApiFacade facade/factory
 */
class ApiFacade
{
    protected $container;

    /**
     *
     * @var \Symfony\Component\HttpKernel\Log\LoggerInterface
     */
    protected $logger;

    public function  __construct(ContainerInterface $container, LoggerInterface $logger)
    {
        $this->container = $container;
        $this->logger = $logger;
    }

    public function getLowLevelApi($apiPath)
    {
        list($entity) = $this->parseApiPath($apiPath);

        return $this->getServiceOrException('mesh.edm_mailchimp.api.low_level.' . $entity);
    }

    public function getHighLevelApi($apiPath)
    {
        list($entity) = $this->parseApiPath($apiPath);

        return $this->getServiceOrException('mesh.edm_mailchimp.api.high_level.' . $entity);
    }

    public function getRequestDataAssembler($apiPath)
    {
        list($entity, $method) = $this->parseApiPath($apiPath);

        return $this->getServiceOrException('mesh.edm_mailchimp.api.request_data_assembler.' . $entity . '_' . $method);
    }

    public function getResponseDataSaver($apiPath)
    {
        list($entity, $method) = $this->parseApiPath($apiPath);

        return $this->getServiceOrException('mesh.edm_mailchimp.api.response_data_saver.' . $entity . '_' . $method);
    }

    /**
     *
     * @return \Mesh\EdmMailChimpBundle\Service\Api\Context\Context
     */
    public function getContext()
    {
        return $this->getServiceOrException('mesh.edm_mailchimp.api.context');
    }

    public function startWorkflow($name)
    {
        $this->getContext()->startWorkflow($name);
    }

    /**
     *
     * @return \Symfony\Component\HttpKernel\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Parse api path like user_list/subscribe and return arrray(user_list, subscribe)
     *
     *
     * @param string $apiPath
     * @return array
     * @throws \LogicException
     */
    protected function parseApiPath($apiPath)
    {
        if (stripos($apiPath, '/') !== false) {
            list($entity, $method) = explode('/', $apiPath);
        } else {
            $entity = $apiPath;
            $method = '';
        }
        
        if (!$entity) {
            throw new \LogicException("Wrong apiPath: $apiPath");
        }

        return array($entity, $method);
    }

    protected function getServiceOrException($service)
    {
        if (!$this->container->has($service)) {
            throw new \LogicException("Can't find service: $service");
        }

        return $this->container->get($service);
    }
}