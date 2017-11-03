<?php

namespace App\RestApiComponent\Request\Parameter;

use App\RestApiComponent\Response\Exception\RestRequestInvalidParameterException;

class FilterDefinition extends AbstractParameterDefinition
{
    /**
     * Example: ['state' => ['eq', 'like']]
     *
     * @var array
     */
    protected $allowedFilterConditions = [];

    /**
     * Example:
     *  [
     *      'state' => ['eq', 'like'],  // allow filter by status with conditions 'eq' or 'like'
     *                                  // corresponds to ?state[like]=active OR state=active
     * 
     *      'name'                      // if only field specified without conditions, then
     *                                  // all available conditions from Filter class allowed
     * ]
     *
     * @param array $conditions
     */
    public function setAllowedFilters($conditions)
    {
        foreach ($conditions as $field => $fieldConditions) {
            if (is_array($fieldConditions) && count(array_diff($fieldConditions, Filter::getConditions()))) {
                throw new \InvalidArgumentException(sprintf(
                    'Wrong conditions \'%s\', supported conditions are: %s',
                    array_diff($fieldConditions, Filter::getConditions()),
                    implode(',', Filter::getConditions())
                ));
            }
        }
        $this->allowedFilterConditions = $conditions;

        // automatically allow filter parameters if we set allowed filters
        if (!empty($this->allowedFilterConditions)) {
            $this->allow();
        }

        return $this;
    }

    /**
     *
     * @param Filter|Filter[] $value
     * @return self
     * @throws \LogicException
     */
    public function setDefaultValue($value)
    {
        if ($value instanceof Filter) {
            $value = [$value];
        }
        if (!is_array($value)) {
            throw new \LogicException("FilterDefinition accept only array of Filter parameter instances as default value");
        }
        foreach ($value as $item) {
            if (!$item instanceof Filter) {
                throw new\LogicException("FilterDefinition accept only array of Filter parameter instances as default value");
            }
        }

        return parent::setDefaultValue($value);
    }

    /**
     * @inheritdoc
     */
    public function parseRequest($request)
    {
        $queryParameters = $this->getQueryParameters($request);
        
        if (array_key_exists(PaginationDefinition::QUERY_KEY, $queryParameters)) {
            unset($queryParameters[PaginationDefinition::QUERY_KEY]);
        }
        if (array_key_exists(SearchDefinition::QUERY_KEY, $queryParameters)) {
            unset($queryParameters[SearchDefinition::QUERY_KEY]);
        }
        if (array_key_exists(SortDefinition::QUERY_KEY, $queryParameters)) {
            unset($queryParameters[SortDefinition::QUERY_KEY]);
        }
        
        // filters don't have special top key in query
        // we assume that all remaining keys in query are filters
        if (!count($queryParameters)) {

            if ($this->defaultValue && $this->isAllowed) {
                return $this->defaultValue;
            }

            return null;
        }

        $filters = [];

        //examples:
        // ?state=active
        // ?name[like]=Max
        // ?name[eq]=Max
        foreach ($queryParameters as $key => $value) {
            $filters[] = $this->parseParameterValue($key, $value);
        }

        return $filters;
    }

    /**
     *
     * @param string $key
     * @param string $value
     * @return \App\RestApiComponent\Request\Parameter\Filter
     * @throws RestRequestInvalidParameterException
     */
    protected function parseParameterValue($key, $value)
    {
        if (is_array($value)) {       // ?name[like]=Max
            if (!count($value)) {
                $publicAndInternalMessage = sprintf(
                    'Request parameter \'%s\' considered as filter and has malformed format. '
                    . '\'%s\' is array but no values set.',
                    $key,
                    $key
                );
                throw new RestRequestInvalidParameterException($publicAndInternalMessage, $publicAndInternalMessage);
            }
            $condition = array_keys($value)[0];
            $filterValue = $value[$condition];
            $condition = strtolower($condition);
        } else {                                    // ?state=active
            $condition = Filter::CONDITION_EQ;      // default condition
            $filterValue = $value;
        }

        if (!in_array($key, $this->getAllowedFilterFields(), true)) {
            $publicAndInternalMessage = sprintf(
                'Request parameter \'%s\' considered as filter but it is not allowed to filter by this field',
                $key
            );
            throw new RestRequestInvalidParameterException($publicAndInternalMessage, $publicAndInternalMessage);
        }

        if (!in_array($condition, $this->getAllowedConditionsForField($key))) {
            $publicAndInternalMessage = sprintf(
                'Request parameter \'%s\' considered as filter but has not allowed filter condition \'%s\'',
                $key,
                $condition
            );
            throw new RestRequestInvalidParameterException($publicAndInternalMessage, $publicAndInternalMessage);
        }

        if (in_array($condition, [Filter::CONDITION_IS_NULL, Filter::CONDITION_IS_NOT_NULL])) {
            // We don't require value in case of `?state[null]`
            $filterValue = null;
        } else {
            if ($this->isEmptyValue($filterValue)) {
                $publicAndInternalMessage = sprintf(
                    'Request parameter \'%s\' considered as filter but has empty value',
                    $key
                );
                throw new RestRequestInvalidParameterException($publicAndInternalMessage, $publicAndInternalMessage);
            }
        }

        return new Filter($key, $condition, $filterValue);
    }

    protected function getAllowedFilterFields()
    {
        $fields = [];

        foreach ($this->allowedFilterConditions as $field => $value) {
            // case when we set only filter name without values
            // ->setAllowedFilters(['status']);
            if (is_numeric($field) && !is_array($value)) {
                $fields[] = $value;
            } else {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    protected function getAllowedConditionsForField($field)
    {
        if (isset($this->allowedFilterConditions[$field])) {
            return $this->allowedFilterConditions[$field];
        } else {
            // here we assume that previosly we check that $field was set using setAllowedFilters
            return Filter::getConditions();
        }
    }
}
