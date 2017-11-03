<?php

namespace App\RestApiComponent\Request;

abstract class AbstractParametersFetcher
{
    protected $parametersDefinitions = [];

    /**
     *
     * @param string $key
     * @param Parameter\ParameterDefinitionInterface $definition
     * @return $this
     */
    protected function addDefinition($key, Parameter\ParameterDefinitionInterface $definition)
    {
        $this->parametersDefinitions[$key] = $definition;

        return $this;
    }

    /**
     * This method could be used to set additional configuration for concrete parameter
     * for example set required/defaultValue and etc ...
     * 
     * @TODO: create magic methods __get for something like getFilter
     *
     * @param string $key
     * @return Parameter\ParameterDefinitionInterface
     */
    protected function getDefinition($key)
    {
        if (!array_key_exists($key, $this->parametersDefinitions)) {
            return null;
        }

        return $this->parametersDefinitions[$key];
    }

    /**
     *  Fetch and parse Request parameters
     * 
     * @param \Zend_Controller_Request_Http | \Symfony\Component\HttpFoundation\Request $request
     * @return ParameterBag
     */
    public function fetch($request)
    {
        $bag = $this->createParameterBag();

        foreach ($this->parametersDefinitions as $key => $definition) {
            $parameter = $definition->parseRequest($request);
            if ($parameter !== null) {
                $bag->add($key, $parameter);
            }
        }

        return $bag;
    }

    /**
     * Method that start configuration chain.
     * Refer to doc in ParametersFetcher\Configurator class
     *
     * @return \App\RestApiComponent\Request\ParametersFetcher\Configurator
     */
    public function configure()
    {
        return new ParametersFetcher\Configurator($this);
    }

    /**
     * @return ParameterBag
     */
    abstract protected function createParameterBag();
}
