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

use Claroline\CoreBundle\Entity\Role;
use Claroline\CoreBundle\Entity\UserRoleCreation;
use Claroline\CoreBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Library\Utilities\ClaroUtilities;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * @DI\Service("claroline.manager.role_creation_manager")
 */
class RoleCreationManager
{
    private $om;
    private $ut;
    
    /**
     * Constructor.
     *
     * @DI\InjectParams({
     *     "om"            = @DI\Inject("claroline.persistence.object_manager"),
     *     "ut"            = @DI\Inject("claroline.utilities.misc")
     * })
     */
    public function __construct(
        ObjectManager $om,
        ClaroUtilities $ut
    )
    {
        $this->om = $om;
        $this->ut = $ut;
    }

    public function createRoleCreation(Role $role)
    {
        $this->om->startFlushSuite();
        
        $roleCreation = new UserRoleCreation($role);
        
        $this->om->persist($roleCreation);
        $this->om->endFlushSuite();

        return $roleCreation;
    }

}