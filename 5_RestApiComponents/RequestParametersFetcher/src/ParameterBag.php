<?php

namespace App\RestApiComponent\Request;

/**
 * @TODO: this class can be simply extended  from Symfony ParameterBag from HttpFoundation component
 * but this will require dependency from that component in composer
 */
class ParameterBag
{
    /**
     *
     * @var Parameter\ParameterInterface[]|Parameter\ParameterInterface[][]
     */
    protected $parameters = [];

    /**
     *
     * @param string $key
     * @param Parameter\ParameterInterface|Parameter\ParameterInterface[] $parameter
     * @return $this
     */
    public function add($key, $parameter)
    {
        $this->parameters[$key] = $parameter;

        return $this;
    }

    public function get($key)
    {
        if (!array_key_exists($key, $this->parameters)) {
            return null;
        }

        return $this->parameters[$key];
    }
}
