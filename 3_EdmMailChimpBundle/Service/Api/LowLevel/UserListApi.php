<?php

namespace Mesh\EdmMailChimpBundle\Service\Api\LowLevel;

use Hype\MailchimpBundle\Mailchimp\MailchimpAPIException;

class UserListApi  extends BaseApi
{
    public function subscribeUsers($listId, $data)
    {
        $this->logInfo('[UserListApi:subscribeUsers] request', $listId, array('options' => $data));

        $response = new MailChimpResponse();

        try {
            
            $content = $this->hypeMailChimp
                ->getList()
                ->setListId($listId)
                ->batchSubscribe($data, false, true, true);

            $response->setContent($content);
            
        } catch(MailchimpAPIException $ex) {
            $response->setErrorMessage($ex->getMessage());
            $this->logError('[UserListApi:subscribeUsers] error: ' . $ex->getMessage(), $listId);
        }

        $this->logInfo('[UserListApi:subscribeUsers] response', $listId, array($response->getContent()));

        return $response;
    }

    public function unsubscribeUsers($listId, $data)
    {
        $this->logInfo('[UserListApi:unsubscribeUsers] request', $listId, array('options' => $data));

        $response = new MailChimpResponse();

        try {

            $content = $this->hypeMailChimp
                ->getList()
                ->setListId($listId)
                ->batchUnsubscribe($data, $delete_member = true, $send_goodbye = false, $send_notify = false);

            $response->setContent($content);

        } catch(MailchimpAPIException $ex) {
            $response->setErrorMessage($ex->getMessage());
            $this->logError('[UserListApi:unsubscribeUsers] error: ' . $ex->getMessage(), $listId);
        }

        $this->logInfo('[UserListApi:unsubscribeUsers] response', $listId, array($response->getContent()));

        return $response;
    }

    public function addMergeField($listId, $data)
    {
        $this->logInfo('[UserListApi:addMergeField] request', $listId, array('options' => $data));

        $response = new MailChimpResponse();

        try {

            $content = $this->hypeMailChimpV3->request(
                'post',
                'lists/' . $listId . '/merge-fields',
                $data
            );
            $content = json_decode($content, true);

            $response->setContent($content);

        } catch(MailchimpAPIException $ex) {
            $response->setErrorMessage($ex->getMessage());
            $this->logError('[UserListApi:addMergeField] error: ' . $ex->getMessage(), $listId);
        }

        $this->logInfo('[UserListApi:addMergeField] response', $listId, array($response->getContent()));

        return $response;
    }
}