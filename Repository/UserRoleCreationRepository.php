<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Entity\Role;

class UserRoleCreationRepository extends EntityRepository
{

    /**
    * 
    * @return userRoleCreation
    */
    public function findOneUserRoleCreation(User $user, $getQuery = false)
    {        
       /* $qb = $this->createQueryBuilder('userRoleCreation');
        $qb->select('userRoleCreation')
            ->where('userRoleCreation.user = :user_id'); // AND userRoleCreation.userRole = :role_id');       

        return $results = $qb->getQuery()->execute(
            array(
                ':user_id'    => $user->getId(),
                ':role_id'    => $role->getId()
            )
        );*/

       $dql = "
            SELECT role_creation
            FROM Claroline\CoreBundle\Entity\UserRoleCreation role_creation
            WHERE role_creation.user = :userId
        ";
        $query = $this->_em->createQuery($dql);
        $query->setParameter('userId',  $user->getId());
        
        return ($getQuery) ? $query: $query->getResult();
    }

   /**
    * 
    * @return userRoleCreation
    */
    public function findUserRoleCreationByUser(User $user, $getQuery = false)
    {        
        $qb = $this->createQueryBuilder('userRoleCreation');
        $qb->select('userRoleCreation')
            ->where('userRoleCreation.user = :user_id');       

        return $results = $qb->getQuery()->execute(
            array(
                ':user_id'    => $user->getId()
            )
        );
    }

   /**
   *
    * @return userRoleCreation
    */
    public function findUserRoleCreationByRole(Role $role, $getQuery = false)
    {        
        $qb = $this->createQueryBuilder('userRoleCreation');
        $qb->select('userRoleCreation')
            ->where('userRoleCreation.userRole = :role_id');       

        return $results = $qb->getQuery()->execute(
            array(
                ':role_id'    => $role->getId()
            )
        );
    }
}
