<?php

namespace App\RestApiComponent\Request\Parameter;

/**
 *  @SWG\Parameter(
 *      parameter="AppRestSearch",
 *      name="q",
 *      description="Search GET parameter. Example: q=active+users",
 *      in="query",
 *      type="string"
 *  )
 */

class Search implements ParameterInterface
{
    /**
     *
     * @var string
     */
    protected $value;

    /**
     *
     * @param string $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
}
