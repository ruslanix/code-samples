<?php

namespace Mesh\EdmMailChimpBundle\Service\Api\HighLevel\ResponseDataSaver;

use Mesh\EdmMailChimpBundle\Service\Api\LowLevel\MailChimpResponse;
use Mesh\EdmMailChimpBundle\Model\EdmMailChimpSynchronizedInterface;
use Mesh\EncompassBundle\Entity\User;

class UserListUnsubscribeDataSaver extends BaseDataSaver
{
    public function saveData(MailChimpResponse $mailChimpResponse, $users, $andFlush = true)
    {
        $this->validateResponse($mailChimpResponse);

        foreach ($users as $user) {
            $this->saveResponseForUser($mailChimpResponse, $user);
        }

        if ($andFlush) {
            $this->getEntityManager()->flush();
        }
    }

    protected function saveResponseForUser(MailChimpResponse $mailChimpResponse, User $user)
    {
        if ($mailChimpResponse->isOk()) {
            $this->saveSuccessResponseForUser($mailChimpResponse->getContent(), $user);
        } else {
            $this->saveErrorResponseForUser($mailChimpResponse, $user);
        }
    }

    protected function saveSuccessResponseForUser($content, User $user)
    {
        foreach ($content['errors'] as $error) {
            if ($error['email'] != $user->getEmail()) {
                continue;
            }

            $code = isset($error['code']) ? $error['code'] : 'unknown';
            $error = isset($error['error']) ? $error['error'] : 'unknown';

            $errorMessage = sprintf('Batch unsubscribe error : [ %s ] %s', $code, $error);

            $user->addEdmMcErrorMessage($errorMessage);
            $user->setEdmMcUpdatedAtAsDate(new \DateTime());
            $user->setEdmMcUpdatedStatus(EdmMailChimpSynchronizedInterface::SYNC_STATUS_ERROR);

            return;
        }

        // if here, user has been unsubscribed successfully
        $user->setEdmMcId(null);
        $user->setEdmMcUpdatedAtAsDate(new \DateTime());
        $user->setEdmMcUpdatedStatus(EdmMailChimpSynchronizedInterface::SYNC_STATUS_NONE);
    }

    protected function saveErrorResponseForUser(MailChimpResponse $mailChimpResponse, User $user)
    {
        $errorMessage = '';

        if ($mailChimpResponse->hasError()) {
            $errorMessage .= 'Error: ' . $mailChimpResponse->getErrorMessage() . '. ';
        }

        if ($mailChimpResponse->hasValidationErrors()) {
            $errorMessage .= 'Validation errors: ' . print_r($mailChimpResponse->getValidationErrors(), true) . '. ';
        }

        if (!$mailChimpResponse->hasContent()) {
            $errorMessage .= 'Response has empty content';
        }

        $user->addEdmMcErrorMessage($errorMessage);
        $user->setEdmMcUpdatedAtAsDate(new \DateTime());
        $user->setEdmMcUpdatedStatus(EdmMailChimpSynchronizedInterface::SYNC_STATUS_ERROR);
    }

    protected function validateResponse(MailChimpResponse $mailChimpResponse)
    {
        if ($mailChimpResponse->hasError() || !$mailChimpResponse->hasContent()) {
            return;
        }

        $content = $mailChimpResponse->getContent();

        if (!isset($content['success_count'])) {
            $mailChimpResponse->addValidationError("Can't find [success_count] key in response");
        }

        if (!isset($content['errors']) && !is_array($content['errors'])) {
            $mailChimpResponse->addValidationError("Field [errors] is not an array");
        }

        foreach ($content['errors'] as $error) {
            if (!isset($error['email'])) {
                $mailChimpResponse->addValidationError("Can't find [email] key in errors");
            }
        }
    }
}