<?php

namespace App\RestApiComponent\Request\Parameter;

/**
 *  @SWG\Parameter(
 *      parameter="AppRestSort",
 *      name="sort",
 *      description="Sort GET parameter. Example: sort=status,date;desc=date",
 *      in="query",
 *      type="string"
 *  )
 */

class Sort implements ParameterInterface
{
    const ORDER_ASC = 'ASC';
    const ORDER_DESC = 'DESC';

    /**
     * Sort field name
     *
     * @var string
     */
    protected $field;

    /**
     * Sort order
     *
     * @var string
     */
    protected $order;

    /**
     *
     * @param string $field
     * @param string $order (ASC|DESC)
     * @throws \InvalidArgumentException
     */
    public function __construct($field, $order)
    {
        if (!in_array($order, [self::ORDER_ASC, self::ORDER_DESC])) {
            throw new \InvalidArgumentException(sprintf(
                'Wrong sort order \'%s\', supported orders are: %s',
                $order,
                implode(',', [self::ORDER_ASC, self::ORDER_DESC])
            ));
        }

        $this->field = $field;
        $this->order = $order;
    }

    /**
     *
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     *
     * @return string (ASC|DESC)
     */
    public function getOrder()
    {
        return $this->order;
    }
}
