<?php

namespace Mesh\EdmMailChimpBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Mesh\EdmMailChimpBundle\Service\Api\ApiFacade;
use Mesh\EncompassBundle\Entity\EdmEmail;

/**
 * Validate EdmEmail audience settings and test that there are some recipients exists for such audience
 */
class EdmEmailAudienceValidator extends ConstraintValidator
{
    /**
     *
     * @var \Mesh\EdmMailChimpBundle\Service\Api\ApiFacade
     */
    protected $apiFacade;

    public function __construct(ApiFacade $apiFacade)
    {
        $this->apiFacade = $apiFacade;
    }

    public function validate($edmEmail, Constraint $constraint)
    {
        if ($this->isAudienceSettingsEmpty($edmEmail))  {
            return;
        }

        $cnt = $this->apiFacade->getHighLevelApi('edm_email')->getAudienceCount($edmEmail);

        if ($cnt === false) {
            $this->context->addViolation(
                $constraint->errorMessage,
                array(),
                null
            );
        } else if ($cnt == 0) {
            $this->context->addViolation(
                $constraint->message,
                array(),
                null
            );
        }
    }

    protected function isAudienceSettingsEmpty(EdmEmail $edmEmail)
    {
        $ageMax = $edmEmail->getEdmAudienceGroup() ? $edmEmail->getEdmAudienceGroup()->getAgeMax() : null;
        $ageMin = $edmEmail->getEdmAudienceGroup() ? $edmEmail->getEdmAudienceGroup()->getAgeMin() : null;
        $gender = $edmEmail->getEdmAudienceGroup() ? $edmEmail->getEdmAudienceGroup()->getGender() : null;
        
        if (!$ageMax && !$ageMin && !$gender) {
            return true;
        }

        return false;
    }
}