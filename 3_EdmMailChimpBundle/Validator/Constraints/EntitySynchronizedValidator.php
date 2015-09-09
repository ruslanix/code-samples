<?php

namespace Mesh\EdmMailChimpBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Mesh\EdmMailChimpBundle\Model\EdmMailChimpSynchronizedInterface;

/**
 * Validate entity that it is synchronized with MailChimp
 */
class EntitySynchronizedValidator extends ConstraintValidator
{
    public function validate($entity, Constraint $constraint)
    {
        if (!$entity instanceof EdmMailChimpSynchronizedInterface) {
            return;
        }

        if (!$entity->getEdmMcId()) {
            $this->context->addViolation(
                $constraint->message,
                array(),
                null
            );
        }
    }
}