<?php

namespace App\RestApiComponent\Request\Parameter;

class SearchDefinition extends AbstractParameterDefinition
{
    const QUERY_KEY = 'q';


    /**
     *
     * @param string|Value $value
     * @return self
     * @throws \LogicException
     */
    public function setDefaultValue($value)
    {
        if (is_string($value)) {
            $value = new Search($value);
        }

        if (!$value instanceof Search) {
            throw new \LogicException("SearchDefinition accept only instance of Search as default value");
        }

        return parent::setDefaultValue($value);
    }

    /**
     * @inheritdoc
     */
    public function parseRequest($request)
    {
        $queryParameters = $this->getQueryParameters($request);
        
        if (!array_key_exists(self::QUERY_KEY, $queryParameters)) {

            if ($this->defaultValue && $this->isAllowed) {
                return $this->defaultValue;
            }

            return null;
        }

        $this->checkIsAllowedOrException(self::QUERY_KEY);

        $value = $queryParameters[self::QUERY_KEY];

        return new Search((string)$value);
    }
}
