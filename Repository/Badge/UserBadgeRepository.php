<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Repository\Badge;

use Claroline\CoreBundle\Entity\Badge\Badge;
use Claroline\CoreBundle\Entity\User;
use Doctrine\ORM\EntityRepository;

class UserBadgeRepository extends EntityRepository
{
    /**
     * @param Badge $badge
     * @param User  $user
     *
     * @return \Claroline\CoreBundle\Entity\Badge\UserBadge|null
     */
    public function findOneByBadgeAndUser(Badge $badge, User $user)
    {
        return $this->findOneBy(array('badge' => $badge, 'user' => $user));
    }

    /**
     * @param User $user
     *
     * @param bool $executeQuery
     *
     * @return Query|array
     */
    public function findByUser(User $user, $executeQuery = true)
    {
        $query = $this->getEntityManager()
            ->createQuery(
                'SELECT ub, b, bt
                FROM ClarolineCoreBundle:Badge\UserBadge ub
                JOIN ub.badge b
                JOIN b.translations bt
                WHERE ub.user = :userId'
            )
            ->setParameter('userId', $user->getId());

        return $executeQuery ? $query->getResult(): $query;
    }
}
