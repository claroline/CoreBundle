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

use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace;
use Doctrine\ORM\EntityRepository;

class WorkspaceRepository extends EntityRepository
{
    /**
     * Returns the workspaces a user is member of.
     *
     * @param User $user
     *
     * @return array[AbstractWorkspace]
     */
    public function findByUser(User $user)
    {
        $dql = '
            SELECT w, r FROM Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace w
            JOIN w.roles r
            JOIN r.users u
            WHERE u.id = :userId
        ';
        $query = $this->_em->createQuery($dql);
        $query->setParameter('userId', $user->getId());

        return $query->getResult();
    }

    /**
     * Returns the workspaces which are not a user's personal workspace.
     *
     * @return array[AbstractWorkspace]
     */
    public function findNonPersonal()
    {
        $dql = '
            SELECT w FROM Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace w
            WHERE w.id NOT IN (
                SELECT pws.id FROM Claroline\CoreBundle\Entity\User user
                JOIN user.personalWorkspace pws
            )
            ORDER BY w.id
        ';
        $query = $this->_em->createQuery($dql);

        return $query->getResult();
    }

    /**
     * Returns the workspaces whose at least one tool is accessible to anonymous users.
     *
     * @return array[AbstractWorkspace]
     */
    public function findByAnonymous()
    {
        $dql = "
            SELECT DISTINCT w FROM Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace w
            JOIN w.orderedTools ot
            JOIN ot.roles r
            WHERE r.name = 'ROLE_ANONYMOUS'
        ";
        $query = $this->_em->createQuery($dql);

        return $query->getResult();
    }

    /**
     * Counts the workspaces.
     *
     * @return integer
     */
    public function count()
    {
        $dql = 'SELECT COUNT(w) FROM Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace w';
        $query = $this->_em->createQuery($dql);

        return $query->getSingleScalarResult();
    }

    /**
     * Returns the workspaces whose at least one tool is accessible to one of the given roles.
     *
     * @param string[] $roles
     *
     * @return array[AbstractWorkspace]
     */
    public function findByRoles(array $roles)
    {
        $dql = "
            SELECT DISTINCT w FROM Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace w
            JOIN w.orderedTools ot
            JOIN ot.roles r
            WHERE r.name in (:roles)
            ORDER BY w.name
        ";

        $query = $this->_em->createQuery($dql);
        $query->setParameter('roles', $roles);

        return $query->getResult();
    }

    /**
     * Returns the workspaces whose at least one tool is accessible to one of the given roles.
     *
     * @param array[string] $roleNames
     *
     * @return array[AbstractWorkspace]
     */
    public function findByRoleNames(array $roleNames)
    {
        $dql = '
            SELECT DISTINCT w
            FROM Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace w
            JOIN w.orderedTools ot
            JOIN ot.roles r
            WHERE r.name IN (:roleNames)
            ORDER BY w.name
        ';

        $query = $this->_em->createQuery($dql);
        $query->setParameter('roleNames', $roleNames);

        return $query->getResult();
    }

    /**
     * Returns the workspaces whose at least one tool is accessible to one of the given roles
     * and whose name matches the given search string.
     *
     * @param array[string] $roleNames
     * @param string        $search
     *
     * @return array[AbstractWorkspace]
     */
    public function findByRoleNamesBySearch(array $roleNames, $search)
    {
        $dql = '
            SELECT DISTINCT w
            FROM Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace w
            JOIN w.orderedTools ot
            JOIN ot.roles r
            WHERE r.name IN (:roleNames)
            AND (
                UPPER(w.name) LIKE :search
                OR UPPER(w.code) LIKE :search
            )
            ORDER BY w.name
        ';

        $upperSearch = strtoupper($search);
        $query = $this->_em->createQuery($dql);
        $query->setParameter('roleNames', $roleNames);
        $query->setParameter('search', "%{$upperSearch}%");

        return $query->getResult();
    }

    /**
     * Returns the ids of the workspaces a user is member of, filtered by a set of roles
     * the user must have in those workspaces. Role names are actually prefixes of the
     * target role (e.g. 'ROLE_WS_COLLABORATOR' instead of 'ROLE_WS_COLLABORATOR_123').
     *
     * @param User          $user
     * @param array[string] $roleNames
     *
     * @return array
     */
    public function findIdsByUserAndRoleNames(User $user, array $roleNames)
    {
        return $this->doFindByUserAndRoleNames($user, $roleNames, true);
    }

    /**
     * Returns the workspaces a user is member of, filtered by a set of roles the user
     * must have in those workspaces. Role names are actually prefixes of the target
     * role (e.g. 'ROLE_WS_COLLABORATOR' instead of 'ROLE_WS_COLLABORATOR_123').
     *
     * @param User          $user
     * @param array[string] $roleNames
     *
     * @return array[AbstractWorkspace]
     */
    public function findByUserAndRoleNames(User $user, array $roleNames)
    {
        return $this->doFindByUserAndRoleNames($user, $roleNames);
    }

    /**
     * Returns the workspaces a user is member of, filtered by a set of roles the user
     * must have in those workspaces, and optionnaly excluding workspaces by id. Role
     * names are actually prefixes of the target role (e.g. 'ROLE_WS_COLLABORATOR'
     * instead of 'ROLE_WS_COLLABORATOR_123').
     *
     * @param User           $user
     * @param array[string]  $roleNames
     * @param array[integer] $restrictionIds
     *
     * @return array[AbstractWorkspace]
     */
    public function findByUserAndRoleNamesNotIn(User $user, array $roleNames, array $restrictionIds = null)
    {
        if ($restrictionIds === null || count($restrictionIds) === 0) {
            return $this->findByUserAndRoleNames($user, $roleNames);
        }

        $rolesRestriction = '';
        $first = true;

        foreach ($roleNames as $roleName) {
            if ($first) {
                $first = false;
                $rolesRestriction .= "(r.name like '{$roleName}_%'";
            } else {
                $rolesRestriction .= " OR r.name like '{$roleName}_%'";
            }
        }

        $rolesRestriction .= ')';
        $dql = "
            SELECT w FROM Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace w
            JOIN w.roles r
            JOIN r.users u
            WHERE u.id = :userId
            AND {$rolesRestriction}
            AND w.id NOT IN (:restrictionIds)
            ORDER BY w.name
        ";
        $query = $this->_em->createQuery($dql);
        $query->setParameter('userId', $user->getId());
        $query->setParameter('restrictionIds', $restrictionIds);

        return $query->getResult();
    }

    /**
     * Returns the latest workspaces a user has visited.
     *
     * @param User          $user
     * @param array[string] $roles
     * @param integer       $max
     *
     * @return array
     */
    public function findLatestWorkspacesByUser(User $user, array $roles, $max = 5)
    {
        $dql = "
            SELECT DISTINCT w AS workspace, MAX(l.dateLog) AS max_date
            FROM Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace w
            INNER JOIN Claroline\CoreBundle\Entity\Log\Log l WITH l.workspace = w
            JOIN l.doer u
            WHERE l.action = 'workspace-tool-read'
            AND u.id = :userId
            GROUP BY w.id
            ORDER BY max_date DESC
        ";

        $query = $this->_em->createQuery($dql);
        $query->setMaxResults($max);
        $query->setParameter('userId', $user->getId());

        return $query->getResult();
    }

    /**
     * Returns the name, code and number of resources of each workspace.
     *
     * @param integer $max
     *
     * @return array
     */
    public function findWorkspacesWithMostResources($max)
    {
        $qb = $this
            ->createQueryBuilder('ws')
            ->select('ws.name, ws.code, COUNT(rs.id) AS total')
            ->leftJoin('Claroline\CoreBundle\Entity\Resource\ResourceNode', 'rs', 'WITH', 'ws = rs.workspace')
            ->groupBy('ws.id')
            ->orderBy('total', 'DESC');

        if ($max > 1) {
            $qb->setMaxResults($max);
        }

        return $qb->getQuery()->getResult();
    }

    private function doFindByUserAndRoleNames(User $user, array $roleNames, $idsOnly = false)
    {
        $rolesRestriction = '';
        $first = true;

        foreach ($roleNames as $roleName) {
            if ($first) {
                $first = false;
                $rolesRestriction .= "(r.name like '{$roleName}_%'";
            } else {
                $rolesRestriction .= " OR r.name like '{$roleName}_%'";
            }
        }

        $rolesRestriction .= ')';
        $select = $idsOnly ? 'w.id' : 'w';
        $dql = "
            SELECT {$select} FROM Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace w
            JOIN w.roles r
            JOIN r.users u
            WHERE u.id = :userId
            AND {$rolesRestriction}
        ";
        $query = $this->_em->createQuery($dql);
        $query->setParameter('userId', $user->getId());

        return $query->getResult();
    }

    /**
     * Returns the workspaces which are marked as displayable.
     *
     * @return array[AbstractWorkspace]
     */
    public function findDisplayableWorkspaces()
    {
        $dql = '
            SELECT w
            FROM Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace w
            WHERE w.displayable = true
            ORDER BY w.name
        ';
        $query = $this->_em->createQuery($dql);

        return $query->getResult();
    }

    /**
     * Returns the workspaces which are visible for an authenticated user and allow
     * self-registration (user's workspaces are excluded).
     *
     * @param User $user
     *
     * @return array[AbstractWorkspace]
     */
    public function findWorkspacesWithSelfRegistration(User $user)
    {
        $dql = '
            SELECT w
            FROM Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace w
            WHERE w.displayable = true
            AND w.selfRegistration = true
            AND w.id NOT IN (
                SELECT w2.id FROM Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace w2
                JOIN w2.roles r
                JOIN r.users u
                WHERE u.id = :userId
            )
            ORDER BY w.name
        ';
        $query = $this->_em->createQuery($dql);
        $query->setParameter('userId', $user->getId());

        return $query->getResult();
    }

    /**
     * Returns the workspaces which are visible for each user
     * and where name or code contains $search param.
     *
     * @return array[AbstractWorkspace]
     */
    public function findDisplayableWorkspacesBySearch($search)
    {
        $dql = '
            SELECT w
            FROM Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace w
            WHERE w.displayable = true
            AND (
                UPPER(w.name) LIKE :search
                OR UPPER(w.code) LIKE :search
            )
            ORDER BY w.name
        ';

        $search = strtoupper($search);
        $query = $this->_em->createQuery($dql);
        $query->setParameter('search', "%{$search}%");

        return $query->getResult();
    }

    public function findWorkspacesWithSelfUnregistrationByRoles(array $roles)
    {
        $dql = "
            SELECT DISTINCT w FROM Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace w
            JOIN w.orderedTools ot
            JOIN ot.roles r
            WHERE w.selfUnregistration = true
            AND r.name IN (:roles)
            ORDER BY w.name
        ";

        $query = $this->_em->createQuery($dql);
        $query->setParameter('roles', $roles);

        return $query->getResult();
    }

    /**
     * Returns the workspaces which are visible and are not in the given list.
     *
     * @return array[AbstractWorkspace]
     */
    public function findDisplayableWorkspacesWithout(array $excludedWorkspaces)
    {
        $dql = '
            SELECT w
            FROM Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace w
            WHERE w.displayable = true
            AND w NOT IN (:excludedWorkspaces)
            ORDER BY w.name
        ';
        $query = $this->_em->createQuery($dql);
        $query->setParameter('excludedWorkspaces', $excludedWorkspaces);

        return $query->getResult();
    }

    /**
     * Returns the workspaces which are visible, are not in the given list
     * and whose name or code contains $search param.
     *
     * @return array[AbstractWorkspace]
     */
    public function findDisplayableWorkspacesWithoutBySearch(
        array $excludedWorkspaces,
        $search
    )
    {
        $dql = '
            SELECT w
            FROM Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace w
            WHERE w.displayable = true
            AND (
                UPPER(w.name) LIKE :search
                OR UPPER(w.code) LIKE :search
            )
            AND w NOT IN (:excludedWorkspaces)
            ORDER BY w.name
        ';
        $upperSearch = strtoupper($search);
        $query = $this->_em->createQuery($dql);
        $query->setParameter('search', "%{$upperSearch}%");
        $query->setParameter('excludedWorkspaces', $excludedWorkspaces);

        return $query->getResult();
    }

    public function findWorkspaceByWorkspaceAndRoles(
        AbstractWorkspace $workspace,
        array $roles
    )
    {
        if (count($roles > 0)) {
            $dql = "
                SELECT DISTINCT w
                FROM Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace w
                JOIN w.orderedTools ot
                JOIN ot.roles r
                WHERE w = :workspace
                AND r.name IN (:roles)
            ";

            $query = $this->_em->createQuery($dql);
            $query->setParameter('workspace', $workspace);
            $query->setParameter('roles', $roles);

            return $query->getOneOrNullResult();
        }

        return null;
    }

    public function findByName($search, $executeQuery = true, $orderedBy = 'id')
    {
        $upperSearch = strtoupper($search);
        $upperSearch = trim($upperSearch);
        $upperSearch = preg_replace('/\s+/', ' ', $upperSearch);
        $dql = "
            SELECT w
            FROM Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace w
            WHERE w.name LIKE :search
            OR UPPER(w.code) LIKE :search
            ORDER BY w.{$orderedBy}
        ";

        $query = $this->_em->createQuery($dql);
        $query->setParameter('search', "%{$upperSearch}%");

        return $executeQuery ? $query->getResult() : $query;
    }

    public function findWorkspacesByManager(User $user, $executeQuery = true)
    {
        $roles = $user->getRoles();
        $managerRoles = [];

        foreach ($roles as $role) {
            if (strpos('_'.$role, 'ROLE_WS_MANAGER')) {
                $managerRoles[] = $role;
            }
        }

        $dql = "
            SELECT w
            FROM Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace w
            JOIN w.roles r
            WHERE r.name IN (:roleNames)

        ";

        $query = $this->_em->createQuery($dql);
        $query->setParameter('roleNames', $managerRoles);

        return $executeQuery ? $query->getResult() : $query;
    }

    public function findWorkspacesByCode(array $codes)
    {
        $dql = "
            SELECT w
            FROM Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace w
            WHERE w.code IN (:codes)
            ";

        $query = $this->_em->createQuery($dql);
        $query->setParameter('codes', $codes);

        return $query->getResult();
    }

    public function countUsers($workspaceId)
    {
        $dql = '
            SELECT count(w) FROM Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace w
            JOIN w.roles r
            JOIN r.users u
            WHERE w.id = :workspaceId
        ';
        $query = $this->_em->createQuery($dql);
        $query->setParameter('workspaceId', $workspaceId);

        return $query->getSingleScalarResult();
    }

    /**
     * Returns the workspaces accessible by one of the given roles.
     *
     * @param array[string] $roleNames
     *
     * @return array[AbstractWorkspace]
     */
    public function findMyWorkspacesByRoleNames(array $roleNames)
    {
        $dql = '
            SELECT DISTINCT w
            FROM Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace w
            WHERE w IN (
                SELECT rw.id
                FROM Claroline\CoreBundle\Entity\Role r
                JOIN Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace rw
                WHERE r.name IN (:roleNames)
            )
            ORDER BY w.name ASC
        ';

        $query = $this->_em->createQuery($dql);
        $query->setParameter('roleNames', $roleNames);

        return $query->getResult();
    }
}
