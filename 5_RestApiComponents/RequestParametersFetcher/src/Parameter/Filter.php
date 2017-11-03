<?php

namespace App\RestApiComponent\Request\Parameter;

class Filter implements ParameterInterface
{
    const CONDITION_EQ = 'eq';
    const CONDITION_LIKE = 'like';
    const CONDITION_IS_NULL = 'null'; // means that filtered property should be null
    const CONDITION_IS_NOT_NULL = 'notnull'; // means that filtered property should be not null

    /**
     *
     * @return string[]
     */
    public static function getConditions()
    {
        return [
            self::CONDITION_EQ,
            self::CONDITION_LIKE,
            self::CONDITION_IS_NULL,
            self::CONDITION_IS_NOT_NULL
        ];
    }

    /**
     * Filtered field name
     *
     * @var string
     */
    protected $field;

    /**
     * Filter value
     *
     * @var string
     */
    protected $value;

    /**
     * Filter condition
     *
     * @var string
     */
    protected $condition;

    /**
     *
     * @param string $field
     * @param string $condition
     * @param string $value
     * @throws \InvalidArgumentException
     */
    public function __construct($field, $condition, $value)
    {
        $this->field = $field;
        $this->value = $value;

        if (!in_array($condition, self::getConditions())) {
            throw new \InvalidArgumentException(sprintf(
                'Wrong condition \'%s\', supported conditions are: %s',
                $condition,
                implode(',', self::getConditions())
            ));
        }

        // `null` | `notnull` conditions don't require `value`
        // set it to null explicitly
        if (in_array($condition, [self::CONDITION_IS_NOT_NULL, self::CONDITION_IS_NULL])) {
            $this->value = null;
        }

        $this->condition = $condition;
    }

    /**
     *  Filtered field name
     *
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Filter value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Filter condition
     *
     * @return string
     */
    public function getCondition()
    {
        return $this->condition;
    }
}
