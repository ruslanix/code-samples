<?php

namespace xxx;

use xxx\BaseTestCase;

use xxx\TypeTimeValidator;
use xxx as Constraint;

/**
 * Unit tests for TimeValidator
 */
class TypeTimeValidatorTest extends BaseTestCase
{
    public function providerIsValid()
    {
        return array(

            array('10:20 PM',   true),
            array('10:20 AM',   true),            
            array('02:05 PM',   true),
            array('02:59 PM',   true),
            array('12:59 PM',   true),
            array('1:59 PM',    true),

            array('00:00 PM',   false),
            array('00:00 AM',   false),
            array('02:60 PM',   false),
            array('13:30 PM',   false),
            array('13:20 KM',   false),
            array('13:20',      false),
            array('1:2 AM',     false),
            array('0:0 AM',     false),
        );
    }

    /**
     * Test for method isValid
     *
     * @param string $value
     * @param boolean $exptectedResult
     *
     * @dataProvider providerIsValid
     */
    public function testIsValid($value, $exptectedResult)
    {
        $validator = new TypeTimeValidator();

        $this->assertEquals(
            $exptectedResult,
            $validator->isValid($value, new Constraint()),
            "Wrong validation result for answer value: {$value}"
        );
    }
}