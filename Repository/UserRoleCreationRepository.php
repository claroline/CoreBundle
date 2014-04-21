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
    * @param ResourceInstance $user
    * @return userRoleCreation
    */
    public function findUserUserRoleCreation(User $user, Role $role, $getQuery = false)
    {        
        $qb = $this->createQueryBuilder('userRoleCreation');
        $qb->select('userRoleCreation')
            ->where('userRoleCreation.user = :user_id AND userRoleCreation.userRole = :role_id');       

        return $results = $qb->getQuery()->execute(
            array(
                ':user_id'    => $user->getId(),
                ':role_id'    => $role->getId()
            )
        );
    }
}
