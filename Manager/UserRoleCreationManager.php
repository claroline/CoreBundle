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

use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Entity\Role;
use Claroline\CoreBundle\Entity\UserRoleCreation;
use Claroline\CoreBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Library\Utilities\ClaroUtilities;
use Claroline\CoreBundle\Repository\UserRepository;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * @DI\Service("claroline.manager.user_role_creation_manager")
 */
class UserRoleCreationManager
{
    private $om;
    private $ut;
    private $userRepo;
    private $userRoleCreationRepo;
    
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
        $this->userRepo = $om->getRepository('ClarolineCoreBundle:User');
        $this->userRoleCreationRepo = $om->getRepository('ClarolineCoreBundle:UserRoleCreation');
    }

    //public function createUserRoleCreation(AbstractRoleSubject $ars, Role $role)
    public function createUserRoleCreation(User $user, Role $role)
    {
        /*$users = $this->userRepo->findByRoles($roles);
        Lequel User est le notre parmis les users !!! ????
        Le ARS est un probleme !!!*/
        $this->om->startFlushSuite();
        $userRoleCreation = new UserRoleCreation($user, $role);
        
        $this->om->persist($userRoleCreation);
        $this->om->endFlushSuite();

        return $userRoleCreation;
    }

    //public function removeRoleCreation(User $user, Role $role)
}