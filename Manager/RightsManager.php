<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Manager;

use JMS\DiExtraBundle\Annotation as DI;
use Claroline\CoreBundle\Event\StrictDispatcher;
use Claroline\CoreBundle\Entity\Role;
use Claroline\CoreBundle\Entity\Resource\ResourceNode;
use Claroline\CoreBundle\Entity\Resource\ResourceRights;
use Claroline\CoreBundle\Repository\ResourceNodeRepository;
use Claroline\CoreBundle\Repository\RoleRepository;
use Claroline\CoreBundle\Repository\ResourceTypeRepository;
use Claroline\CoreBundle\Repository\ResourceRightsRepository;
use Claroline\CoreBundle\Manager\RoleManager;
use Claroline\CoreBundle\Manager\MaskManager;
use Claroline\CoreBundle\Persistence\ObjectManager;
use Symfony\Component\Translation\Translator;

/**
 * @DI\Service("claroline.manager.rights_manager")
 */
class RightsManager
{
    /** @var MaskManager */
    private $maskManager;
    /** @var ResourceRightsRepository */
    private $rightsRepo;
    /** @var ResourceNodeRepository */
    private $resourceRepo;
    /** @var RoleRepository */
    private $roleRepo;
    /** @var ResourceTypeRepository */
    private $resourceTypeRepo;
    /** @var Translator */
    private $translator;
    /** @var ObjectManager */
    private $om;
    /** @var StrictDispatcher */
    private $dispatcher;
    /** @var RoleManager */
    private $roleManager;

    /**
     * Constructor.
     *
     * @DI\InjectParams({
     *     "translator"  = @DI\Inject("translator"),
     *     "om"          = @DI\Inject("claroline.persistence.object_manager"),
     *     "dispatcher"  = @DI\Inject("claroline.event.event_dispatcher"),
     *     "roleManager" = @DI\Inject("claroline.manager.role_manager"),
     *     "maskManager" = @DI\Inject("claroline.manager.mask_manager")
     * })
     */
    public function __construct(
        Translator $translator,
        ObjectManager $om,
        StrictDispatcher $dispatcher,
        RoleManager $roleManager,
        MaskManager $maskManager
    )
    {
        $this->rightsRepo = $om->getRepository('ClarolineCoreBundle:Resource\ResourceRights');
        $this->resourceRepo = $om->getRepository('ClarolineCoreBundle:Resource\ResourceNode');
        $this->roleRepo = $om->getRepository('ClarolineCoreBundle:Role');
        $this->resourceTypeRepo = $om->getRepository('ClarolineCoreBundle:Resource\ResourceType');
        $this->translator = $translator;
        $this->om = $om;
        $this->dispatcher = $dispatcher;
        $this->roleManager = $roleManager;
        $this->maskManager = $maskManager;
    }

    /**
     * Create a new ResourceRight
     *
     * @param array|integer $permissions
     * @param \Claroline\CoreBundle\Entity\Role $role
     * @param \Claroline\CoreBundle\Entity\Resource\ResourceNode $resource
     * @param boolean $isRecursive
     * @param array $creations
     * @internal param \Claroline\CoreBundle\Entity\Resource\ResourceNode $node
     */
    public function create(
        $permissions,
        Role $role,
        ResourceNode $node,
        $isRecursive,
        array $creations = array()
    )
    {
        $isRecursive ?
            $this->recursiveCreation($permissions, $role, $node, $creations) :
            $this->nonRecursiveCreation($permissions, $role, $node, $creations);
    }

    /**
     * @param integer                                            $permissions the permission mask
     * @param \Claroline\CoreBundle\Entity\Role                  $role
     * @param \Claroline\CoreBundle\Entity\Resource\ResourceNode $node
     * @param boolean                                            $isRecursive
     *
     * @return array|\Claroline\CoreBundle\Entity\Resource\ResourceRights[]
     */
    public function editPerms(
        $permissions,
        Role $role,
        ResourceNode $node,
        $isRecursive
    )
    {
        //Bugfix: If the flushSuite is uncommented, doctrine returns an error
        //(ResourceRights duplicate)
        //$this->om->startFlushSuite();

        $arRights = $isRecursive ?
            $this->updateRightsTree($role, $node):
            array($this->getOneByRoleAndResource($role, $node));

        foreach ($arRights as $toUpdate) {
            if ($isRecursive) {
                if (is_int($permissions)) {
                    $permissions = $this->mergeTypePermissions($permissions, $toUpdate->getMask());
                } else {
                    $resourceType = $toUpdate->getResourceNode()->getResourceType();
                    $permissionsMask = $this->maskManager->encodeMask($permissions, $resourceType);
                    $permissionsMask = $this->mergeTypePermissions($permissionsMask, $toUpdate->getMask());
                    $permissions = $this->maskManager->decodeMask($permissionsMask, $resourceType);
                }
            }

            is_int($permissions) ?
                $toUpdate->setMask($permissions) :
                $this->setPermissions($toUpdate, $permissions);

            $this->om->persist($toUpdate);
            $this->logChangeSet($toUpdate);
        }

        //$this->om->endFlushSuite();
        return $arRights;
    }

    /**
     * @param array                                              $resourceTypes
     * @param \Claroline\CoreBundle\Entity\Role                  $role
     * @param \Claroline\CoreBundle\Entity\Resource\ResourceNode $node
     * @param boolean                                            $isRecursive
     *
     * @return \Claroline\CoreBundle\Entity\Resource\ResourceRight[] $arRights
     */
    public function editCreationRights(
        array $resourceTypes,
        Role $role,
        ResourceNode $node,
        $isRecursive
    )
    {
        //Bugfix: If the flushSuite is uncommented, doctrine returns an error
        //(ResourceRights duplicata)
        //$this->om->startFlushSuite();

        $arRights = ($isRecursive) ?
            $this->updateRightsTree($role, $node):
            array($this->getOneByRoleAndResource($role, $node));

        foreach ($arRights as $toUpdate) {
            $toUpdate->setCreatableResourceTypes($resourceTypes);
            $this->om->persist($toUpdate);
            $this->logChangeSet($toUpdate);
        }

        //$this->om->endFlushSuite();
        return $arRights;
    }

    /**
     * Copy the rights from the parent to its children.
     *
     * @param \Claroline\CoreBundle\Entity\Resource\ResourceNode $original
     * @param \Claroline\CoreBundle\Entity\Resource\ResourceNode $node
     */
    public function copy(ResourceNode $original, ResourceNode $node)
    {
        $originalRights = $this->rightsRepo->findBy(array('resourceNode' => $original));
        $this->om->startFlushSuite();

        foreach ($originalRights as $originalRight) {
            $new = $this->om->factory('Claroline\CoreBundle\Entity\Resource\ResourceRights');
            $new->setRole($originalRight->getRole());
            $new->setResourceNode($node);
            $new->setMask($originalRight->getMask());
            $new->setCreatableResourceTypes($originalRight->getCreatableResourceTypes()->toArray());
            $this->om->persist($new);
        }

        $this->om->endFlushSuite();
    }

    /**
     * Create rights wich weren't created for every descendants and returns every rights of
     * every descendants (include rights wich weren't created).
     *
     * @param \Claroline\CoreBundle\Entity\Role                  $role
     * @param \Claroline\CoreBundle\Entity\Resource\ResourceNode $node
     *
     * @return \Claroline\CoreBundle\Entity\Resource\ResourceRights[]
     */
    public function updateRightsTree(Role $role, ResourceNode $node)
    {
        $alreadyExistings = $this->rightsRepo->findRecursiveByResourceAndRole($node, $role);
        $descendants = $this->resourceRepo->findDescendants($node, true);
        $finalRights = array();

        foreach ($descendants as $descendant) {
            $found = false;

            foreach ($alreadyExistings as $existingRight) {
                if ($existingRight->getResourceNode() === $descendant) {
                    $finalRights[] = $existingRight;
                    $found = true;
                }
            }

            if (!$found) {
                $rights = $this->om->factory('Claroline\CoreBundle\Entity\Resource\ResourceRights');
                $rights->setRole($role);
                $rights->setResourceNode($descendant);
                $this->om->persist($rights);
                $finalRights[] = $rights;
            }
        }

        $this->om->flush();

        return $finalRights;
    }

    /**
     * Set the permission for a resource right.
     * The array of permissions should be defined that way:
     * array('open' => true, 'edit' => false, ...)
     *
     * @param \Claroline\CoreBundle\Entity\Resource\ResourceRights $rights
     * @param array                                                $permissions
     *
     * @return \Claroline\CoreBundle\Entity\Resource\ResourceRights
     */
    public function setPermissions(ResourceRights $rights, array $permissions)
    {
        $resourceType = $rights->getResourceNode()->getResourceType();
        $rights->setMask($this->maskManager->encodeMask($permissions, $resourceType));

        return $rights;
    }

    /**
     * Takes an array of Role.
     * Parse each key of the $perms array
     * and add the entry 'role' where it is needed.
     * It's used when a workspace is imported
     *
     * @param array $baseRoles
     * @param array $perms
     *
     * @return array
     */
    public function addRolesToPermsArray(array $baseRoles, array $perms)
    {
        $initializedArray = array();

        foreach ($perms as $roleBaseName => $data) {
            foreach ($baseRoles as $baseRole) {
                if ($this->roleManager->getRoleBaseName($baseRole->getName()) === $roleBaseName) {
                    $data['role'] = $baseRole;
                    $initializedArray[$roleBaseName] = $data;
                }
            }
        }

        return $initializedArray;
    }

    /**
     * @param \Claroline\CoreBundle\Entity\Role                  $role
     * @param \Claroline\CoreBundle\Entity\Resource\ResourceNode $node
     *
     * @return \Claroline\CoreBundle\Entity\Resource\ResourceRights $resourceRights
     */
    public function getOneByRoleAndResource(Role $role, ResourceNode $node)
    {
        $resourceRights = $this->rightsRepo->findOneBy(array('resourceNode' => $node, 'role' => $role));

        if ($resourceRights === null) {
            $resourceRights = $this->om->factory('Claroline\CoreBundle\Entity\Resource\ResourceRights');
            $resourceRights->setResourceNode($node);
            $resourceRights->setRole($role);
        }

        return $resourceRights;
    }

    /**
     * @param string[]                                           $roles
     * @param \Claroline\CoreBundle\Entity\Resource\ResourceNode $node
     *
     * @return array
     */
    public function getCreatableTypes(array $roles, ResourceNode $node)
    {
        $creatableTypes = array();
        $creationRights = $this->rightsRepo->findCreationRights($roles, $node);

        if (count($creationRights) !== 0) {
            foreach ($creationRights as $type) {
                $creatableTypes[$type['name']] = $this->translator->trans($type['name'], array(), 'resource');
            }
        }

        return $creatableTypes;
    }

    /**
     * @param integer|array                                      $permissions
     * @param \Claroline\CoreBundle\Entity\Role                  $role
     * @param \Claroline\CoreBundle\Entity\Resource\ResourceNode $node
     * @param array                                              $creations
     */
    public function recursiveCreation(
        $permissions,
        Role $role,
        ResourceNode $node,
        array $creations = array()
    )
    {
        $this->om->startFlushSuite();
        //will create every rights with the role and the resource already set.
        $resourceRights = $this->updateRightsTree($role, $node);

        foreach ($resourceRights as $rights) {
            is_int($permissions) ? $rights->setMask($permissions): $this->setPermissions($rights, $permissions);
            $rights->setCreatableResourceTypes($creations);
            $this->om->persist($rights);
        }

        $this->om->endFlushSuite();
    }

    /**
     * @param integer|array                                      $permissions
     * @param \Claroline\CoreBundle\Entity\Role                  $role
     * @param \Claroline\CoreBundle\Entity\Resource\ResourceNode $node
     * @param array                                              $creations
     */
    public function nonRecursiveCreation(
        $permissions,
        Role $role,
        ResourceNode $node,
        array $creations = array()
    )
    {
        $rights = $this->om->factory('Claroline\CoreBundle\Entity\Resource\ResourceRights');
        $rights->setRole($role);
        $rights->setResourceNode($node);
        $rights->setCreatableResourceTypes($creations);
        is_int($permissions) ? $rights->setMask($permissions): $this->setPermissions($rights, $permissions);
        $this->om->persist($rights);
        $this->om->flush();
    }

    /**
     * @param \Claroline\CoreBundle\Entity\Resource\ResourceRights $rights
     */
    public function logChangeSet(ResourceRights $rights)
    {
        $uow = $this->om->getUnitOfWork();
        $uow->computeChangeSets();
        $changeSet = $uow->getEntityChangeSet($rights);

        if (count($changeSet) > 0) {
            $this->dispatcher->dispatch(
                'log',
                'Log\LogWorkspaceRoleChangeRight',
                array($rights->getRole(), $rights->getResourceNode(), $changeSet)
            );
        }
    }

    /**
     * Returns every ResourceRights of a resource on 1 level if the role linked is not 'ROLE_ADMIN'
     *
     * @param \Claroline\CoreBundle\Entity\Resource\ResourceNode $node
     *
     * @return \Claroline\CoreBundle\Resource\ResourceRights[]ù
     */
    public function getConfigurableRights(ResourceNode $node)
    {
        return $this->rightsRepo->findConfigurableRights($node);
    }

    /**
     * @return \Claroline\CoreBundle\Entity\Resource\ResourceType[]
     */
    public function getResourceTypes()
    {
        return $this->resourceTypeRepo->findAll();
    }

    /**
     * @param array                                              $roles
     * @param \Claroline\CoreBundle\Entity\Resource\ResourceNode $node
     *
     * @return type
     */
    public function getMaximumRights(array $roles, ResourceNode $node)
    {
        return $this->rightsRepo->findMaximumRights($roles, $node);
    }

    /**
     * @param string[]                                           $roles
     * @param \Claroline\CoreBundle\Entity\Resource\ResourceNode $node
     *
     * @return array
     */
    public function getCreationRights(array $roles, ResourceNode $node)
    {
        return $this->rightsRepo->findCreationRights($roles, $node);
    }

    /**
     * Merges permissions related to a specific resource type (i.e. "post" in a
     * forum) with a directory mask. This allows directory permissions to be
     * applied recursively without loosing particular permissions.
     *
     * @param int $dirMask          A directory mask
     * @param int $resourceMask     A specific resource mask
     * @return int
     */
    private function mergeTypePermissions($dirMask, $resourceMask)
    {
        // extract base permissions ("open", "edit", etc. -> i.e. 5 out of 32
        // possible permissions) by getting the last 5 bits of the mask
        $baseMask = $resourceMask % 32;
        // keep only specific permissions
        $typeMask = $resourceMask - $baseMask;

        return $dirMask | $typeMask; // merge
    }
}
