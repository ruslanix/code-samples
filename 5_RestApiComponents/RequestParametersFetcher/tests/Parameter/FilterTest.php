<?php
namespace App\RestApiComponent\Tests\Request\Parameter;

use App\RestApiComponent\Request\Parameter\Filter;

class FilterTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $filter = new Filter('param1', Filter::CONDITION_EQ, 'value');

        $this->assertEquals('param1', $filter->getField());
        $this->assertEquals(Filter::CONDITION_EQ, $filter->getCondition());
        $this->assertEquals('value', $filter->getValue());
    }

    public function testConstructor_SetValueToNullInCaseOf_Null_NotNull_Conditions()
    {
        $filterWithNullCondition = new Filter('parameter1', Filter::CONDITION_IS_NULL, 'fake value');
        $filterWithNotNullCondition = new Filter('parameter1', Filter::CONDITION_IS_NOT_NULL, 'fake value');

        $this->assertNull($filterWithNullCondition->getValue());
        $this->assertNull($filterWithNotNullCondition->getValue());
    }
}