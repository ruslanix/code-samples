<?php

namespace Mesh\EdmMailChimpBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validate entity that it is synchronized with MailChimp
 *
 * @Annotation
 */
class EntitySynchronized extends Constraint
{
    public $message = 'Environment (site) is not ready for sending emails. Please, try later or contact administrator.';
}