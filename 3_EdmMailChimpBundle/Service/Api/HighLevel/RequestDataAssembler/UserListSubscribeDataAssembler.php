<?php

namespace Mesh\EdmMailChimpBundle\Service\Api\HighLevel\RequestDataAssembler;

use Mesh\EncompassBundle\Entity\User;
use Mesh\EncompassBundle\Entity\Interests;

class UserListSubscribeDataAssembler
{
    public function assembleData($users)
    {
        $data = array();

        foreach ($users as $user) {
            $data[] = $this->getUserData($user);
        }

        return $data;
    }

    protected function getUserData(User $user)
    {
        $data = array();

        $data['email'] = array('email' => $user->getEmail());
        $data['merge_vars'] = array(
            'FNAME' => $user->getFirstName(),
            'LNAME' => $user->getLastName()
        );

        if ($user->getGender()) {
            $data['merge_vars']['GENDER'] = lcfirst($user->getGender());
        }

        if ($user->getYearOfBirth()) {
            $date = \DateTime::createFromFormat("Y-m-d", $user->getYearOfBirth() . "-01-01");
            if ($date) {
                $data['merge_vars']['BIRTHDATE'] = $date->format('Y-m-d');
            }
        }

        $grouping = $this->getUserGrouping($user);
        if ($grouping) {
            $data['merge_vars']['groupings'] = $grouping;
        }

        return $data;
    }

    protected function getUserGrouping(User $user)
    {
        if (!$user->getInterests()) {
            return false;
        }

        // All root interests mapped to one Mailchimp category, so get first root
        $categoryId = $user->getInterests()->first()->getParent()->getEdmMcId();
        $options = array();

        foreach ($user->getInterests() as $interest) {
            $options[] = $interest->getEdmMcSerializedObjectFieldOrException('name');
        }

        $grouping[] = array(
            'id' => $categoryId,
            'groups' => $options
        );

        return $grouping;
    }
}