<?php

namespace Mesh\EdmMailChimpBundle\Model\Behaviour\Mutator;

use JMS\Serializer\Annotation as Serializer;

use Mesh\EdmMailChimpBundle\Model\EdmMailChimpSynchronizedInterface;

trait EdmMailChimpSynchronizedBehaviour
{
    protected static function getSyncStatuses()
    {
        return array(
            EdmMailChimpSynchronizedInterface::SYNC_STATUS_UPDATED,
            EdmMailChimpSynchronizedInterface::SYNC_STATUS_OUTDATED,
            EdmMailChimpSynchronizedInterface::SYNC_STATUS_ERROR,
        );
    }

    /**
     * @ORM\Column(name="edm_mc_id", type="string", length=50, nullable=true)
     *
     * @Serializer\Groups({"edm"})
     */
    protected $edm_mc_id;

    /**
     * @ORM\Column(name="edm_mc_updated_status", type="string", length=30, nullable=true)
     * 
     * @Serializer\Groups({"edm"})
     */
    protected $edm_mc_updated_status = EdmMailChimpSynchronizedInterface::SYNC_STATUS_NONE;

    /**
     * @ORM\Column(name="edm_mc_updated_at", type="datetime", nullable=true)
     *
     * @Assert\DateTime
     *
     * @Serializer\Groups({"edm"})
     * @Serializer\Type("DateTime<'Y-m-d\TH:i:sO'>")
     */
    protected $edm_mc_updated_at;

    /**
     * @ORM\Column(name="edm_mc_data", type="json_array", nullable=true)
     *
     * @Serializer\Groups({"edm"})
     * @Serializer\Type("array")
     */
    protected $edm_mc_data;

    public function edmMailChimpSynchronizedConstructor()
    {
        $this->edm_mc_data = array();
    }

    public function getEdmMcId()
    {
        return $this->edm_mc_id;
    }

    public function setEdmMcId($id)
    {
        $this->edm_mc_id = $id;

        return $this;
    }

    public function getEdmMcUpdatedStatus()
    {
        return $this->edm_mc_updated_status;
    }

    public function setEdmMcUpdatedStatus($status)
    {
        $this->edm_mc_updated_status = $status;

        return $this;
    }

    public function getEdmMcUpdatedAt()
    {
        return $this->edm_mc_updated_at;
    }

    public function setEdmMcUpdatedAt($updatedAt)
    {
        $this->edm_mc_updated_at = new \DateTime($updatedAt);

        return $this;
    }

    public function setEdmMcUpdatedAtAsDate(\DateTime $updatedAt)
    {
        $this->edm_mc_updated_at = $updatedAt;

        return $this;
    }

    public function setEdmMcData($data)
    {
        $this->edm_mc_data = $data;

        if (!is_array($this->edm_mc_data)) {
            $this->edm_mc_data = array();
        }

        return $this;
    }

    public function getEdmMcData()
    {
        if (!is_array($this->edm_mc_data)) {
            $this->edm_mc_data = array();
        }
        return $this->edm_mc_data;
    }

    public function clearEdmMcData()
    {
        $this->edm_mc_data = array();
    }

    public function addEdmMcErrorMessage($errorMessage)
    {
        if (!isset($this->edm_mc_data['error_message']) || !is_array($this->edm_mc_data['error_message'])) {
            $this->edm_mc_data['error_message'] = array();
        }

        $this->edm_mc_data['error_message'][] = $errorMessage;

        return $this;
    }

    /**
     * @Serializer\Groups({"details"})
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("mc_error")
     */
    public function getEdmMcErrorMessage()
    {
        if (
            isset($this->edm_mc_data['error_message'])
            && is_array($this->edm_mc_data['error_message'])
            && count($this->edm_mc_data['error_message'])
        ) {
            return $this->edm_mc_data['error_message'][count($this->edm_mc_data['error_message']) - 1];
        }

        return false;
    }

    public function setEdmMcSerializedObject($data)
    {
        $this->edm_mc_data['serialized_object'] = $data;

        return $this;
    }

    public function getEdmMcSerializedObject()
    {
        if (isset($this->edm_mc_data['serialized_object'])) {
            return $this->edm_mc_data['serialized_object'];
        }

        return false;
    }

    public function isEdmMcStatusOk()
    {
        return $this->edm_mc_updated_status == EdmMailChimpSynchronizedInterface::SYNC_STATUS_UPDATED;
    }

    public function setEdmMcSubEntityErrorMessage($subEntityName, $errorMessage)
    {
        if (!isset($this->edm_mc_data['subentities_data'])) {
            $this->edm_mc_data['subentities_data'] = array();
        }

        $this->edm_mc_data['subentities_data'][$subEntityName]['error_message'] = $errorMessage;

        return $this;
    }

    public function getEdmMcSubEntityErrorMessage($subEntityName)
    {
        if (!isset($this->edm_mc_data['subentities_data'])) {
            $this->edm_mc_data['subentities_data'] = array();
        }

        if (isset($this->edm_mc_data['subentities_data'][$subEntityName]) && isset($this->edm_mc_data['subentities_data'][$subEntityName]['error_message'])) {
            return $this->edm_mc_data['subentities_data'][$subEntityName]['error_message'];
        }

        return false;
    }

    public function setEdmMcSubEntitySerializedObject($subEntityName, $data)
    {
        if (!isset($this->edm_mc_data['subentities_data'])) {
            $this->edm_mc_data['subentities_data'] = array();
        }

        $this->edm_mc_data['subentities_data'][$subEntityName]['serialized_object'] = $data;

        return $this;
    }

    public function getEdmMcSubEntitySerializedObject($subEntityName)
    {
        if (!isset($this->edm_mc_data['subentities_data'])) {
            $this->edm_mc_data['subentities_data'] = array();
        }

        if (isset($this->edm_mc_data['subentities_data'][$subEntityName]) && isset($this->edm_mc_data['subentities_data'][$subEntityName]['serialized_object'])) {
            return $this->edm_mc_data['subentities_data'][$subEntityName]['serialized_object'];
        }

        return false;
    }

    public function getEdmMcSerializedObjectFieldOrException($field)
    {
        $mcData = $this->getEdmMcSerializedObject();

        if (!is_array($mcData) || !array_key_exists($field, $mcData)) {
            throw new \LogicException("Can't find mc data '$field' in entity {$this->getId()}, data: " . print_r($mcData, true));
        }

        return $mcData[$field];
    }
}