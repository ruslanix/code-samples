<?php

namespace App\RestApiComponent\Request\Parameter;

interface ParameterDefinitionInterface
{
    /**
     * Set default value
     * should be one of the possible value for concrete parameter
     */
    public function setDefaultValue($value);

    /**
     * Allow this parameter in request
     */
    public function allow();

    /**
     * Deny this parameter in request
     */
    public function deny();

    /**
     * Extract parameter value(s) from request
     *
     * @param \Zend_Controller_Request_Http | \Symfony\Component\HttpFoundation\Request $request
     *
     * @return mixed
     */
    public function parseRequest($request);
}
