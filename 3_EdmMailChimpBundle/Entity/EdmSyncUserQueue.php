<?php
namespace Mesh\EdmMailChimpBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation\MaxDepth;

use Mesh\EncompassBundle\Behaviour\Mutator\IdBehaviour;
use Mesh\EncompassBundle\Behaviour\Mutator\DateBehaviour;
use Mesh\EncompassBundle\Behaviour\Mutator\SiteBehaviour;
use Mesh\EncompassBundle\Behaviour\ConstructorBehaviour;

/**
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Mesh\EdmMailChimpBundle\Repository\EdmSyncUserQueueRepository")
 * @ORM\HasLifecycleCallbacks
 */
class EdmSyncUserQueue
{
    use IdBehaviour,
        DateBehaviour,
        SiteBehaviour,
        ConstructorBehaviour;

    const STATUS_PROCESSING = 'processing';
    const STATUS_PROCESSED = 'processed';
    const STATUS_WAITING = 'waiting';
    const STATUS_ERROR = 'error';

    public static function getStatuses()
    {
        return array(
            self::STATUS_PROCESSING,
            self::STATUS_PROCESSED,
            self::STATUS_WAITING,
            self::STATUS_ERROR
        );
    }
    
    /**
     * @ORM\ManyToOne(targetEntity="Mesh\EncompassBundle\Entity\Site", cascade={"persist"})
     * @ORM\JoinColumn(name="site_id", referencedColumnName="id", nullable=false)
     * @Assert\Valid
     * @Assert\NotBlank
     * @Assert\NotNull
     *
     * @Serializer\Groups({"details"})
     */
    protected $site;

    /**
     * @ORM\ManyToOne(targetEntity="Mesh\EncompassBundle\Entity\User", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     * 
     * @Serializer\Groups({"details"})
     * @MaxDepth(1)
     */
    protected $user;

    /**
     * @ORM\Column(name="status", type="string", length=30, nullable=false)
     *
     * @Assert\NotBlank
     * @Assert\NotNull
     * @Assert\Choice(callback = "getStatuses")
     *
     * @Serializer\Groups({"details", "list"})
     */
    protected $status = self::STATUS_WAITING;

    public function __construct() {

        $this->execute();
    }

    /**
     * Set user
     *
     * @param \Mesh\EncompassBundle\Entity\User $user
     * @return \Mesh\EdmMailChimpBundle\Entity\EdmSyncUserQueue
     */
    public function setUser(\Mesh\EncompassBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Mesh\EncompassBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     *
     * @param string $status
     * @return \Mesh\EdmMailChimpBundle\Entity\EdmSyncUserQueue
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }
}
