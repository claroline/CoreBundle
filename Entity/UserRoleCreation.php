<?php

namespace Claroline\CoreBundle\Entity;

use Symfony\Bridge\Doctrine\Validator\Constraints as DoctrineAssert;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Claroline\CoreBundle\Entity\AbstractRoleSubject;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Entity\Role;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="Claroline\CoreBundle\Repository\RoleCreationRepository")
 * @ORM\Table(name="claro_user_role_creation")
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
     *     targetEntity="Claroline\CoreBundle\Entity\Role"
     * )
     * @ORM\JoinColumn(nullable=false)
     */
    protected $userRole;

    /**
     *
     * @ORM\OneToOne(
        targetEntity="Claroline\CoreBundle\Entity\User",
        cascade={"persist"}
       )
     * @ORM\JoinColumn(nullable=false, unique=true)
     */
    protected $user;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="creation_date", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $creationDate;

    public function __construct($user, $role)
    {
        $this->user = $user;
        $this->userRole = $role;
    }
    
    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
    *   @return User
    */
    public function getUser()
    {
        return $this->user;
    }

    public function getUserRole()
    {
        return $this->userRole;
    }

    public function setUserRole($role)
    {
        $this->userRole = $role;
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
