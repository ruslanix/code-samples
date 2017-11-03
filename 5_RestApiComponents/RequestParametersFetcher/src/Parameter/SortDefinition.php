<?php

namespace App\RestApiComponent\Request\Parameter;

use App\RestApiComponent\Response\Exception\RestRequestInvalidParameterException;

class SortDefinition extends AbstractParameterDefinition
{
    const QUERY_KEY = 'sort';


    /**
     *
     * @param Sort|Sort[] $value
     * @return self
     * @throws \LogicException
     */
    public function setDefaultValue($value)
    {
        if ($value instanceof Sort) {
            $value = [$value];
        }
        if (!is_array($value)) {
            throw new \LogicException("SortDefinition accept only array of Sort parameter instances as default value");
        }
        foreach ($value as $item) {
            if (!$item instanceof Sort) {
                throw new \LogicException("SortDefinition accept only array of Sort parameter instances as default value");
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
     * @inheritdoc
     */
    protected function parseParameterValue($value)
    {
        // validate raw value
        // example: rating,reviews,name;desc=rating,reviews
        $regexp = "#^(?:[\w.]+(?:,[\w.]+)*)(?:;desc=[\w.]+(?:,[\w.]+)*)?$#xsu";
        if (!preg_match($regexp, $value)) {
            $publicAndInternalMessage = sprintf(
                'Request parameter \'%s\' value has malformed format. '
                . 'Example of valid value: \'range=name,status;desc=name\'',
                self::QUERY_KEY
            );
            throw new RestRequestInvalidParameterException($publicAndInternalMessage, $publicAndInternalMessage);
        }

        $values = explode(';', $value);
        $ascString = isset($values[0]) ? $values[0] : null;
        $descString = isset($values[1]) ? $values[1] : null;
        $asc = $ascString ? explode(',', $ascString) : [];
        $desc = $descString ? explode(',', str_replace('desc=', '', $descString)) : [];

        // check that desc part contains only fields from sort part
        if (array_diff($desc, $asc)) {
            $publicAndInternalMessage = sprintf(
                'Request parameter \'%s\' has malformed value - desc part contains fields \'%s\' '
                . 'that are not listed in asc part',
                self::QUERY_KEY,
                implode(',', array_diff($desc, $asc))
            );
            throw new RestRequestInvalidParameterException($publicAndInternalMessage, $publicAndInternalMessage);
        }

        $sorting = [];

        foreach ($asc as $ascField) {
            $order = in_array($ascField, $desc) ? Sort::ORDER_DESC : Sort::ORDER_ASC;
            $sorting[] = new Sort($ascField, $order);
        }

        return $sorting;
    }
}
