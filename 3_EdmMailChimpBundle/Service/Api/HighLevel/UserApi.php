<?php

namespace Mesh\EdmMailChimpBundle\Service\Api\HighLevel;

class UserApi extends BaseApi
{
    public function syncPush($listId, $users)
    {
        $this->logInfo("[UserApi:syncPush] start");

        try {
            $payload = $this->apiFacade->getRequestDataAssembler('user_list/subscribe')->assembleData($users);
            $response = $this->apiFacade->getLowLevelApi('user_list')->subscribeUsers($listId, $payload);
            $this->apiFacade->getResponseDataSaver('user_list/subscribe')->saveData($response, $users);
        } catch(\Exception $ex) {
            foreach ($users as $user) {
                $this->processException($ex, $user);
            }
        }
    }

    public function unsubscribe($listId, $users)
    {
        $this->logInfo("[UserApi:unsubscribe] start");

        try {
            $payload = $this->apiFacade->getRequestDataAssembler('user_list/unsubscribe')->assembleData($users);
            $response = $this->apiFacade->getLowLevelApi('user_list')->unsubscribeUsers($listId, $payload);
            $this->apiFacade->getResponseDataSaver('user_list/unsubscribe')->saveData($response, $users);
        } catch(\Exception $ex) {
            foreach ($users as $user) {
                $this->processException($ex, $user);
            }
        }
    }
}