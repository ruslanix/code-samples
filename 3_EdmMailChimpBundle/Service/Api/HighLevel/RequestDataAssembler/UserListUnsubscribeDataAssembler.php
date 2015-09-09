<?php

namespace Mesh\EdmMailChimpBundle\Service\Api\HighLevel\RequestDataAssembler;

use Mesh\EncompassBundle\Entity\User;

class UserListUnsubscribeDataAssembler
{
    public function assembleData($users)
    {
        $data = array();

        foreach ($users as $user) {
            $data[] = array('email' => $user->getEmail());
        }

        return $data;
    }
}