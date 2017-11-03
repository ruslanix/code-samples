<?php

namespace App\RestApiComponent\Request\Parameter;


/**
 *  @SWG\Parameter(
 *      parameter="AppRestPagination",
 *      name="range",
 *      description="Pagination GET parameter. Example: range=0-25",
 *      in="query",
 *      type="string",
 *      pattern="^(?:\d+-\d+)$",
 *      default="0-25"
 *  )
 */

class Pagination implements ParameterInterface
{
    /**
     * Define is pagination data was presented/requested in Request parameters
     * or it is a pre-defined value that we use when Request doesn't contain pagination data
     * 
     * @var bool
     */
    protected $isRequestedRange = false;

    /**
     * Pagination offset
     * The meaning is the same as MySql OFFSET
     *
     * @var int
     */
    protected $offset;

    /**
     * Pagination limit - the amount of elements that should be returned
     * The meaning is the same as MySql LIMIT
     *
     * @var int
     */
    protected $limit;

    /**
     * In query we set pagination as range.
     * For ex.: ?range=10-20
     * 
     * This function translate range to our internal pagination representation (limit, offset) 
     * and return Pagination object
     *
     * @param int $start
     * @param int $end
     * @return \self
     */
    public static function createFromRange($start, $end)
    {
        return new self($start, $end - $start + 1);
    }

    /**
     *
     * @param int $offset
     * @param int $limit
     */
    public function __construct($offset, $limit)
    {
        $this->offset = (int)$offset;
        $this->limit = (int)$limit;
    }

    /**
     *
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     *
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * Translate internal (limit, offset) representation to range and return range start
     *
     * @return int
     */
    public function getRangeStart()
    {
        return $this->offset;
    }

    /**
     * Translate internal (limit, offset) representation to range and return range start
     * 
     * @return int
     */
    public function getRangeEnd()
    {
        return $this->offset + $this->limit - 1;
    }

    /**
     *
     * @return int
     * @throws \LogicException
     */
    public function getPage()
    {
        if ($this->limit === 0) {
            throw new \LogicException("Can't calculate page when limit is 0");
        }
        return (int)($this->offset/$this->limit);
    }

    /**
     * Check is pagination data was presented/requested in Request parameters
     * or it is a pre-defined value that we use when Request doesn't contain pagination data
     *
     * @return type
     */
    public function isRequestedRange()
    {
        return $this->isRequestedRange;
    }

    /**
     *
     * @param bool $isRequestedRange
     * @return $this
     */
    public function setIsRequestedRange($isRequestedRange)
    {
        $this->isRequestedRange = $isRequestedRange;

        return $this;
    }
}
