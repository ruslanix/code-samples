<?php

namespace Mesh\EdmMailChimpBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validate EdmEmail audience settings and test that there are some recipients exists for such audience
 *
 * @Annotation
 */
class EdmEmailAudience extends Constraint
{
    public $message = 'There are no any subscribers for such audience settings or there are no subscribers at all.';
    public $errorMessage = "Currently system can't process this email. Please, try later or contact administrator.";

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    public function validatedBy()
    {
        return 'validator.mesh.edm_mailchimp.emd_email_audience';
    }
}