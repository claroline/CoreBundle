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

use Doctrine\ORM\EntityRepository;
use Claroline\CoreBundle\Entity\Role;
use Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Entity\Group;
use Claroline\CoreBundle\Entity\Tool\Tool;
use Claroline\CoreBundle\Entity\Tool\AdminTool;
use Claroline\CoreBundle\Entity\Facet\FieldFacet;

class RoleRepository extends EntityRepository
{
    /**
     * Returns the roles associated to a workspace.
     *
     * @param AbstractWorkspace $workspace
     *
     * @return array[AbstractWorkspace]
     */
    public function findByWorkspace(AbstractWorkspace $workspace)
    {
        $dql = "
            SELECT r FROM Claroline\CoreBundle\Entity\Role r
            JOIN r.workspace ws
            WHERE ws.id = :workspaceId
        ";
        $query = $this->_em->createQuery($dql);
        $query->setParameter('workspaceId', $workspace->getId());

        return $query->getResult();
    }

    /**
     * Returns the visitor role of a workspace.
     *
     * @param AbstractWorkspace $workspace
     *
     * @return Role
     */
    public function findVisitorRole(AbstractWorkspace $workspace)
    {
        return $this->findBaseWorkspaceRole('VISITOR', $workspace);
    }

    /**
     * Returns the collaborator role of a workspace.
     *
     * @param AbstractWorkspace $workspace
     *
     * @return Role
     */
    public function findCollaboratorRole(AbstractWorkspace $workspace)
    {
        return $this->findBaseWorkspaceRole('COLLABORATOR', $workspace);
    }

    /**
     * Returns the manager role of a workspace.
     *
     * @param AbstractWorkspace $workspace
     *
     * @return Role
     */
    public function findManagerRole(AbstractWorkspace $workspace)
    {
        return $this->findBaseWorkspaceRole('MANAGER', $workspace);
    }

    /**
     * Returns the platform roles of a user.
     *
     * @param User $user
     *
     * @return array[Role]
     */
    public function findPlatformRoles(User $user)
    {
        $dql = "
            SELECT r FROM Claroline\CoreBundle\Entity\Role r
            JOIN r.users u
            WHERE u.id = {$user->getId()} AND r.type != " . Role::WS_ROLE;
        $query = $this->_em->createQuery($dql);

        return $query->getResult();
    }

    /**
     * Returns all platform roles.
     *
     * @return array[Role]
     */
    public function findAllPlatformRoles()
    {
        $queryBuilder = $this
            ->createQueryBuilder('role')
            ->andWhere("role.type = :roleType")
            ->setParameter("roleType", Role::PLATFORM_ROLE);
        $queryBuilder->andWhere($queryBuilder->expr()->not($queryBuilder->expr()->eq('role.name', '?1')))
            ->setParameter(1, 'ROLE_ANONYMOUS');
        $query = $queryBuilder->getQuery();

        return $query->getResult();
    }

    public function findByUserAndWorkspace(User $user, AbstractWorkspace $workspace)
    {
        $dql = "
            SELECT r FROM Claroline\CoreBundle\Entity\Role r
            JOIN r.users u
            JOIN r.workspace w
            WHERE u.id = :userId AND w.id = :workspaceId
            ";

        $query = $this->_em->createQuery($dql);
        $query->setParameter('userId', $user->getId());
        $query->setParameter('workspaceId', $workspace->getId());

        return $query->getResult();
    }

    public function findByGroupAndWorkspace(Group $group, AbstractWorkspace $workspace)
    {
        $dql = "
            SELECT r FROM Claroline\CoreBundle\Entity\Role r
            JOIN r.groups g
            JOIN r.workspace w
            WHERE g.id = :groupId AND w.id = :workspaceId
            ";

        $query = $this->_em->createQuery($dql);
        $query->setParameter('groupId', $group->getId());
        $query->setParameter('workspaceId', $workspace->getId());

        return $query->getResult();
    }

    /**
     * Returns the roles of a user in a workspace.
     *
     * @param User              $user      The subject of the role
     * @param AbstractWorkspace $workspace The workspace the role should be bound to
     *
     * @return null|Role
     */
    public function findWorkspaceRolesForUser(User $user, AbstractWorkspace $workspace)
    {
        $dql = "
            SELECT r FROM Claroline\CoreBundle\Entity\Role r
            JOIN r.workspace ws
            JOIN r.users user
            WHERE ws.guid = '{$workspace->getGuid()}'
            AND r.name != 'ROLE_ADMIN'
            AND user.id = {$user->getId()}
        ";

        $query = $this->_em->createQuery($dql);

        return $query->getResult();
    }

    /**
     * Returns the roles which have access to a workspace tool.
     *
     * @param \Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace $workspace
     * @param \Claroline\CoreBundle\Entity\Tool\Tool                   $tool
     */
    public function findByWorkspaceAndTool(AbstractWorkspace $workspace, Tool $tool)
    {
        $dql = "
            SELECT DISTINCT r FROM Claroline\CoreBundle\Entity\Role r
            JOIN r.workspace ws
            JOIN ws.orderedTools ot
            JOIN ot.roles r_2
            JOIN ot.tool tool
            WHERE ws.guid = '{$workspace->getGuid()}'
            AND tool.id = {$tool->getId()}
            AND r.id = r_2.id
            AND r.name != 'ROLE_ADMIN'
        ";

        $query = $this->_em->createQuery($dql);

        return $query->getResult();
    }

    public function findRolesByWorkspaceAndRoleNames(
        AbstractWorkspace $workspace,
        array $roles
    )
    {
        $dql = "
            SELECT r FROM Claroline\CoreBundle\Entity\Role r
            JOIN r.workspace w
            WHERE w = :workspace
            AND r.name IN (:roles)
        ";

        $query = $this->_em->createQuery($dql);
        $query->setParameter('workspace', $workspace);
        $query->setParameter('roles', $roles);

        return $query->getResult();
    }

    /**
     * @todo check and document this method
     */
    public function findByWorkspaceCodeTag($workspaceCode)
    {
        $upperSearch = strtoupper($workspaceCode);

        $dql = "
            SELECT DISTINCT r FROM Claroline\CoreBundle\Entity\Role r
            JOIN r.workspace ws
            LEFT JOIN Claroline\CoreBundle\Entity\Workspace\RelWorkspaceTag rwt
            WITH rwt.workspace = ws
            LEFT JOIN Claroline\CoreBundle\Entity\Workspace\WorkspaceTag wt
            WITH rwt.tag = wt AND wt.user IS NULL
            LEFT JOIN Claroline\CoreBundle\Entity\Workspace\WorkspaceTagHierarchy wth
            WITH wth.tag = wt AND wth.user IS NULL
            LEFT JOIN wth.parent p
            WHERE ws.displayable = true AND (UPPER(ws.code) LIKE :code
            OR UPPER(wt.name) LIKE :code
            OR UPPER(p.name) LIKE :code)
        ";

        $query = $this->_em->createQuery($dql);
        $query->setParameter('code', '%'.$upperSearch.'%');

        return $query->getResult();
    }

    private function findBaseWorkspaceRole($roleType, AbstractWorkspace $workspace)
    {
        $dql = "
            SELECT r FROM Claroline\CoreBundle\Entity\Role r
            WHERE r.name = 'ROLE_WS_{$roleType}_{$workspace->getGuid()}'
        ";
        $query = $this->_em->createQuery($dql);

        return $query->getSingleResult();
    }

    public function searchByName($search)
    {
        $upperSearch = strtoupper(trim($search));

        $dql = "
            SELECT r
            FROM Claroline\CoreBundle\Entity\Role r
            WHERE UPPER(r.name) LIKE :search
        ";

        $query = $this->_em->createQuery($dql);
        $query->setParameter('search', "%{$upperSearch}%");

        return $query->getResult();
    }

    public function findAll()
    {
        $dql = "
            SELECT r, w
            FROM Claroline\CoreBundle\Entity\Role r
            LEFT JOIN r.workspace w
        ";

        $query = $this->_em->createQuery($dql);

        return $query->getResult();
    }

    public function findPlatformNonAdminRoles()
    {
        $queryBuilder = $this
            ->createQueryBuilder('role')
            ->andWhere("role.type = :roleType")
            ->setParameter("roleType", Role::PLATFORM_ROLE);
        $queryBuilder->andWhere($queryBuilder->expr()->not($queryBuilder->expr()->eq('role.name', '?1')))
            ->setParameter(1, 'ROLE_ANONYMOUS');
        $queryBuilder->andWhere($queryBuilder->expr()->not($queryBuilder->expr()->eq('role.name', '?2')))
            ->setParameter(2, 'ROLE_ADMIN');
        $query = $queryBuilder->getQuery();

        return $query->getResult();
    }

    public function findAllWhereWorkspaceIsDisplayable()
    {
        $dql = "
            SELECT r, w
            FROM Claroline\CoreBundle\Entity\Role r
            LEFT JOIN r.workspace w
            WHERE w.displayable = true
        ";

        $query = $this->_em->createQuery($dql);

        return $query->getResult();
    }

    public function findByAdminTool(AdminTool $adminTool)
    {
        $dql = "
            SELECT r FROM Claroline\CoreBundle\Entity\Role r
            JOIN r.adminTools t
            WHERE t.id = :id
        ";

        $query = $this->_em->createQuery($dql);
        $query->setParameter('id', $adminTool->getId());

        return $query->getResult();
    }
}
