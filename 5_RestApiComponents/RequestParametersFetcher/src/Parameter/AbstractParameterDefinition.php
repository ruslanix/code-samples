<?php

namespace App\RestApiComponent\Request\Parameter;

use App\RestApiComponent\Response\Exception\RestRequestInvalidParameterException;

abstract class AbstractParameterDefinition implements ParameterDefinitionInterface
{
    /**
     *
     * @var mixed
     */
    protected $defaultValue;

    /**
     * Is this parameter allowed in request
     *
     * @var bool
     */
    protected $isAllowed = false;

    /**
     * Set default value
     * should be one of the possible value for concrete parameter.
     * It will be used if the Param is not set in the Request
     *
     * @param mixed $value
     * @return $this
     */
    public function setDefaultValue($value)
    {
        $this->defaultValue = $value;

        return $this;
    }

    /**
     * Allow this parameter in request
     *
     * @return $this
     */
    public function allow()
    {
        $this->isAllowed = true;

        return $this;
    }

    /**
     * Deny this parameter in request
     *
     * @return $this
     */
    public function deny()
    {
        $this->isAllowed = false;

        return $this;
    }

    protected function getQueryParameters($request)
    {
        if ($request instanceof \Zend_Controller_Request_Http) {
            return $request->getQuery();
        }

        if ($request instanceof \Symfony\Component\HttpFoundation\Request) {
            return $request->query->all();
        }

        throw new \InvalidArgumentException(sprintf(
            'request must be instance of \'%s\' or \'%s\'',
            '\Zend_Controller_Request_Http',
            '\Symfony\Component\HttpFoundation\Request'
        ));
    }

    /**
     *
     * @throws RestRequestInvalidParameterException
     */
    protected function checkIsAllowedOrException($parameterKey)
    {
        if (!$this->isAllowed) {
            $publicAndInternalMessage = sprintf(
                'Request parameter \'%s\' denied.',
                $parameterKey
            );
            throw new RestRequestInvalidParameterException($publicAndInternalMessage, $publicAndInternalMessage);
        }
    }

    protected function isEmptyValue($value)
    {
        return $value === '' || $value === null;
    }
}
