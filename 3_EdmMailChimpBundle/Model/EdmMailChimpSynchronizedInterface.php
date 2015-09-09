<?php

namespace Mesh\EdmMailChimpBundle\Model;

interface EdmMailChimpSynchronizedInterface
{
    /**
     * Statuses
     */
    const SYNC_STATUS_UPDATED = 'updated';
    const SYNC_STATUS_UPDATED_MAIN_ENTITY = 'updated_main_entity';
    const SYNC_STATUS_NONE = 'none';
    const SYNC_STATUS_ERROR = 'error';
    const SYNC_STATUS_SUBENTITY_ERROR = 'subentity_error';

    public function getEdmMcId();
    public function setEdmMcId($id);

    public function getEdmMcUpdatedStatus();
    public function setEdmMcUpdatedStatus($status);
    
    public function getEdmMcUpdatedAt();
    public function setEdmMcUpdatedAt($updatedAt);

    public function setEdmMcData($data);
    public function getEdmMcData();
    public function clearEdmMcData();

    public function addEdmMcErrorMessage($errorMessage);
    public function getEdmMcErrorMessage();


    public function setEdmMcSerializedObject($data);
    public function getEdmMcSerializedObject();
    public function getEdmMcSerializedObjectFieldOrException($field);


    public function setEdmMcSubEntityErrorMessage($subEntityName, $errorMessage);
    public function getEdmMcSubEntityErrorMessage($subEntityName);

    public function setEdmMcSubEntitySerializedObject($subEntityName, $data);
    public function getEdmMcSubEntitySerializedObject($subEntityName);
}