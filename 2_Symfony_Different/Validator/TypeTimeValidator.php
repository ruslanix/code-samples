<?php

namespace xxx;

use xxx\TypeIntegerValidator;
use xxx\TypeInteger;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Time validator
 */
class TypeTimeValidator extends ConstraintValidator
{
    /**
     * Check that value is valid time
     *
     * @param string                                  $value
     * @param \Symfony\Component\Validator\Constraint $constraint
     *
     * @return boolean
     */
    public function isValid($value, Constraint $constraint)
    {
        if (null === $value) {
            return true;
        }

        if (
            !$this->isFormatValid($value)
            ||
            !$this->isValuesValid($value))
        {
            $this->setMessage($constraint->message, array('{{ value }}' => $value));

            return false;
        }

        return true;
    }

    /**
     * Check that value has valid time format
     *
     * @param string $value
     *
     * @return boolean
     */
    protected function isFormatValid($value)
    {
        $regexp = '/^\d{1,2}:\d{2} (AM|PM)$/';

        return preg_match($regexp, $value);
    }

    /**
     * Check that values is valid time values
     *
     * @param string $value
     *
     * @return boolean
     */
    protected function isValuesValid($value)
    {
        $components = $this->parseTimeComponents($value);

        if (!$components) {
            return false;
        }

        foreach ($components as $symbol => $number) {

            if (!$this->isValidInteger($number)) {
                return false;
            }

            if ($symbol == 'h') {
                if (($number <= 0) || ($number > 12)) {
                    return false;
                }
            } else if ($symbol == 'm') {
                if (($number < 0) || ($number > 59)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Parse time components and return
     *  array
     *      'h' => 03
     *      'm' => 30
     *
     * @param string $value
     * 
     * @return array
     */
    protected function parseTimeComponents($value)
    {
        $components = array();

        if (preg_match('/^(\d{1,2}):(\d{2}) (AM|PM)$/', $value, $mathces)) {

            $components['h'] = $mathces[1];
            $components['m'] = $mathces[2];
        }

        return $components;
    }

    /**
     * Check that value is valid integer
     *
     * @param string $value
     *
     * @return boolean
     */
    protected function isValidInteger($value)
    {
        $integerValidator = new TypeIntegerValidator();

        return $integerValidator->isValid($value, new TypeInteger());
    }
}
