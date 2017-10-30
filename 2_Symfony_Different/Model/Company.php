<?php
namespace Market\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use DoctrineExtensions\Versionable\Versionable;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;
use Doctrine\Common\Collections\ArrayCollection;
use Swagger\Annotations as SWG;

/**
 * @ORM\Entity(repositoryClass="Market\Model\Repository\CompanyRepository")
 * @ORM\Table (name="market_companies")
 *
 * @Gedmo\Mapping\Annotation\SoftDeleteable(fieldName="deletedAt")
 *
 * @Serializer\ExclusionPolicy("all")
 * @Serializer\AccessorOrder(
 *     "custom",
 *     custom = {
 *         "id",
 *         "name",
 *         "nameTranslated",
 *         "description",
 *         "descriptionTranslated",
 *         "source",
 *         "profile",
 *         "createdAt"
 *     }
 * )
 *
 * @SWG\Definition(
 *  definition="MarketCompany"
 * )
 */
class Company implements Versionable
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue
     *
     * @Serializer\Expose
     * @Serializer\Accessor(getter="getId",setter="setId")
     * @Serializer\Groups({"Default", "market-company-list", "market-company-details"})
     *
     * @SWG\Property()
     *
     * @var integer
     */
    private $id;

    /**
     * @ORM\Column(name="name", type="string", nullable=false)
     *
     * @Assert\NotBlank
     *
     * @Serializer\Expose
     * @Serializer\Accessor(getter="getName",setter="setName")
     * @Serializer\Groups({"market-company-list", "market-company-details", "Default"})
     *
     * @SWG\Property()
     *
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(name="description", type="string", length=500, nullable=true)
     *
     * @Serializer\Expose
     * @Serializer\Accessor(getter="getDescription",setter="setDescription")
     * @Serializer\Groups({"market-company-list", "market-company-details"})
     *
     * @SWG\Property()
     *
     * @var string
     */
    private $description;

    /**
     * @ORM\ManyToOne(targetEntity="Source")
     * @ORM\JoinColumn(name="source_id", referencedColumnName="id", nullable=false)
     *
     * @Assert\NotBlank
     *
     * @Serializer\Expose
     * @Serializer\AccessType("public_method")
     * @Serializer\Groups({"market-company-details"})
     * @Serializer\MaxDepth(1)
     *
     * @SWG\Property()
     *
     * @var Source
     */
    private $source;

    /**
     * @ORM\ManyToOne(targetEntity="Profile")
     * @ORM\JoinColumn(name="profile_id", referencedColumnName="id", nullable=true)
     *
     * @Serializer\Expose
     * @Serializer\AccessType("public_method")
     * @Serializer\Groups({"market-company-list", "market-company-details"})
     * @Serializer\MaxDepth(1)
     *
     * @SWG\Property()
     *
     * @var Profile
     */
    private $profile;

    /**
     * @ORM\ManyToMany(targetEntity="Contact", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="market_companies_has_contacts",
     *      joinColumns={@ORM\JoinColumn(name="company_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="contact_id", referencedColumnName="id")}
     *      )
     *
     * @Serializer\Expose
     * @Serializer\AccessType("public_method")
     * @Serializer\Groups({"market-company-details"})
     * @Serializer\MaxDepth(1)
     *
     * @SWG\Property(
     *      type="array",
     *      items = @SWG\Items(
     *          ref="#/definitions/MarketContact"
     *      )
     * )
     * 
     * @var Contact[]|ArrayCollection
     */
    private $contacts;

    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     *
     * @Assert\NotBlank
     *
     * @Serializer\Expose
     * @Serializer\Accessor(getter="getCreatedAt",setter="_setCreatedAt")
     * @Serializer\Groups({"market-company-details"})
     *
     * @SWG\Property()
     *
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     *
     * @var \DateTime
     */
    private $deletedAt;

    /**
     * @ORM\Version
     * @ORM\Column(type="integer")
     */
    private $version;

    public function __construct()
    {
        $this->_setCreatedAt(new \DateTime());
        $this->contacts = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * 
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * 
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * 
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     *
     * @return Source
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     *
     * @param Source $source
     * @return $this
     */
    public function setSource(Source $source)
    {
        $this->source = $source;
        return $this;
    }

    /**
     *
     * @return Profile
     */
    public function getProfile()
    {
        return $this->profile;
    }

    /**
     *
     * @param Profile $profile
     * @return $this
     */
    public function setProfile(Profile $profile)
    {
        $this->profile = $profile;
        return $this;
    }


    /**
     * @return Contact[]|ArrayCollection
     */
    public function getContacts()
    {
        return $this->contacts;
    }

    /**
     * @param array $contacts
     *
     * @return $this
     */
    public function setContacts(array $contacts)
    {
        $this->getContacts()->clear();
        foreach ($contacts as $contact) {
            $this->getContacts()->add($contact);
        }
        return $this;
    }


    /**
     * @param string|null $format
     *
     * @return \DateTime|string|null
     */
    public function getCreatedAt($format = '')
    {
        return (($this->createdAt && $format) ? $this->createdAt->format($format) : $this->createdAt);
    }
}