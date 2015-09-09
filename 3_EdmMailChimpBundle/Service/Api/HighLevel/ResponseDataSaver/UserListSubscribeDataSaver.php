<?php

namespace Mesh\EdmMailChimpBundle\Service\Api\HighLevel\ResponseDataSaver;

use Mesh\EdmMailChimpBundle\Service\Api\LowLevel\MailChimpResponse;
use Mesh\EdmMailChimpBundle\Model\EdmMailChimpSynchronizedInterface;
use Mesh\EncompassBundle\Entity\User;

class UserListSubscribeDataSaver extends BaseDataSaver
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
        $addsUpdates = array_merge($content['adds'], $content['updates']);
        $saved = false;

        foreach ($addsUpdates as $addsUpdatesResponse) {
            if ($addsUpdatesResponse['email'] == $user->getEmail()) {
                $user->setEdmMcId($addsUpdatesResponse['euid']);
                $user->setEdmMcUpdatedAtAsDate(new \DateTime());
                $user->setEdmMcUpdatedStatus(EdmMailChimpSynchronizedInterface::SYNC_STATUS_UPDATED);

                $saved = true;
                
                return;
            }
        }

        foreach ($content['errors'] as $errorResponse) {
            if (!isset($errorResponse['email']) || !is_array($errorResponse['email']) || !isset($errorResponse['email']['email'])) {
                continue;
            }

            if ($errorResponse['email']['email'] == $user->getEmail()) {
                $code = isset($errorResponse['code']) ? $errorResponse['code'] : 'unknown';
                $error = isset($errorResponse['error']) ? $errorResponse['error'] : 'unknown';
                $row = isset($errorResponse['row']) ? $errorResponse['row'] : 'unknown';

                $errorMessage = sprintf('Batch subscribe error : [ %s ] %s , row: %s', $code, $error, print_r($row, true));

                if (isset($errorResponse['euid']) && !empty($errorResponse['euid']))  {
                    $user->setEdmMcId($errorResponse['euid']);
                }
                $user->addEdmMcErrorMessage($errorMessage);
                $user->setEdmMcUpdatedAtAsDate(new \DateTime());
                $user->setEdmMcUpdatedStatus(EdmMailChimpSynchronizedInterface::SYNC_STATUS_ERROR);

                $saved = true;

                return;
            }
        }

        if (!$saved) {
            $user->addEdmMcErrorMessage("Can't find this user in mailchimp response, but should");
            $user->setEdmMcUpdatedAtAsDate(new \DateTime());
            $user->setEdmMcUpdatedStatus(EdmMailChimpSynchronizedInterface::SYNC_STATUS_ERROR);
        }
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

        if (!isset($content['adds']) || !is_array($content['adds'])) {
            $mailChimpResponse->addValidationError("Can't find [adds] key in response or it is not an array");
        }

        if (!isset($content['updates']) || !is_array($content['updates'])) {
            $mailChimpResponse->addValidationError("Can't find [updates] key in response or it is not an array");
        }

        if (!isset($content['errors']) || !is_array($content['errors'])) {
            $mailChimpResponse->addValidationError("Can't find [errors] key in response or it is not an array");
        }
    }
}