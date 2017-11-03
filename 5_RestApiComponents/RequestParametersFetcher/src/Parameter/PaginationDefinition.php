<?php

namespace App\RestApiComponent\Request\Parameter;

use App\RestApiComponent\Response\Exception\RestRequestInvalidParameterException;

class PaginationDefinition extends AbstractParameterDefinition
{
    const QUERY_KEY = 'range';
    const DEFAULT_PAGINATION_MAX_LIMIT = 100;

    /**
     *
     * @var int
     */
    protected $paginationMaxLimit = self::DEFAULT_PAGINATION_MAX_LIMIT;

    /**
     *
     * @param int $paginationMaxLimit
     * @return $this
     */
    public function setPaginationMaxLimit($paginationMaxLimit)
    {
        $this->paginationMaxLimit = $paginationMaxLimit;

        return $this;
    }

    /**
     *
     * @return int
     */
    public function getPaginationMaxLimit()
    {
        return $this->paginationMaxLimit;
    }

    /**
     *
     * @param Pagination $value
     * @return self
     * @throws \LogicException
     */
    public function setDefaultValue($value)
    {
        if (!$value instanceof Pagination) {
            throw new \LogicException("PaginationDefinition accept only instance of Pagination as default value");
        }

        // mark default pagination as not parsed from request parameters
        $value->setIsRequestedRange(false);

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

        return $this->parseParameterValue($value);
    }

    /**
     *
     * @param string $value
     * @return \App\RestApiComponent\Request\Parameter\Pagination
     * @throws RestRequestInvalidParameterException
     */
    protected function parseParameterValue($value)
    {
        // validate raw value
        // example 0-25
        $regexp = "#^(?:\d+-\d+)$#xsu";
        if (!preg_match($regexp, $value)) {
            $publicAndInternalMessage = sprintf(
                'Request parameter \'%s\' value has malformed format. Example of valid value: \'10-21\'. '
                .' Expression requirements: \'%s\'',
                self::QUERY_KEY,
                '\d+-\d+'
            );
            throw new RestRequestInvalidParameterException($publicAndInternalMessage, $publicAndInternalMessage);
        }

        list($start, $end) = explode('-', $value);
        $start = (int)$start;
        $end = (int)$end;

        if ($start >= $end) {
            $publicAndInternalMessage = sprintf(
                'Request parameter \'%s\' has not valid range value. Range start in bigger or equal to end',
                self::QUERY_KEY
            );
            throw new RestRequestInvalidParameterException($publicAndInternalMessage, $publicAndInternalMessage);
        }

        $pagination = Pagination::createFromRange($start, $end);

        if ($pagination->getLimit() > $this->paginationMaxLimit) {
            $publicAndInternalMessage = sprintf(
                'Request parameter \'%s\' value exceed pagination limit %s',
                self::QUERY_KEY,
                $this->paginationMaxLimit
            );
            throw new RestRequestInvalidParameterException($publicAndInternalMessage, $publicAndInternalMessage);
        }

        // mark pagination as parsed from request parameters
        $pagination->setIsRequestedRange(true);

        return $pagination;
    }
}
