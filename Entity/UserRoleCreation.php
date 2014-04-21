<?php

namespace Claroline\CoreBundle\Entity;

use Symfony\Bridge\Doctrine\Validator\Constraints as DoctrineAssert;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Claroline\CoreBundle\Entity\AbstractRoleSubject;
use Claroline\CoreBundle\Entity\User;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="Claroline\CoreBundle\Repository\RoleCreationRepository")
 * @ORM\Table(name="claro_role_creation")
 */
class UserRoleCreation
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

     /**
     *
     * @ORM\ManyToOne(
     *     targetEntity="Claroline\CoreBundle\Entity\Role",
     *     fetch="EXTRA_LAZY"
     * )
     * @ORM\JoinTable(name="claro_user_role_creation")
     */
    protected $userRole;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="creation_date", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $creationDate;

    public function __construct($role)
    {
        $this->userRole = $role;
    }
    
    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
    
    public function getuserRole()
    {
        return $this->userRole;
    }

    /**
     * @return \DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }
    
    /**
     *
     * @param \DateTime $date
     */
    public function setCreationDate(\DateTime $date)
    {
        $this->creationDate = $date;
    }    
}
