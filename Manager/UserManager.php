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

use Claroline\CoreBundle\Entity\Group;
use Claroline\CoreBundle\Entity\Role;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Entity\Workspace\Workspace;
use Claroline\CoreBundle\Event\StrictDispatcher;
use Claroline\CoreBundle\Library\Configuration\PlatformConfigurationHandler;
use Claroline\CoreBundle\Library\Security\PlatformRoles;
use Claroline\CoreBundle\Library\Workspace\Configuration;
use Claroline\CoreBundle\Pager\PagerFactory;
use Claroline\CoreBundle\Persistence\ObjectManager;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\DiExtraBundle\Annotation as DI;
use Claroline\CoreBundle\Manager\Exception\AddRoleException;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\ValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @DI\Service("claroline.manager.user_manager")
 */
class UserManager
{
    private $platformConfigHandler;
    private $strictEventDispatcher;
    private $mailManager;
    private $objectManager;
    private $pagerFactory;
    private $personalWsTemplateFile;
    private $roleManager;
    private $toolManager;
    private $translator;
    private $userRepo;
    private $validator;
    private $workspaceManager;
    private $uploadsDirectory;
    private $container;
    private $resourceNodeRepo;

    /**
     * Constructor.
     *
     * @DI\InjectParams({
     *     "personalWsTemplateFile" = @DI\Inject("%claroline.param.templates_directory%"),
     *     "mailManager"            = @DI\Inject("claroline.manager.mail_manager"),
     *     "objectManager"          = @DI\Inject("claroline.persistence.object_manager"),
     *     "pagerFactory"           = @DI\Inject("claroline.pager.pager_factory"),
     *     "platformConfigHandler"  = @DI\Inject("claroline.config.platform_config_handler"),
     *     "roleManager"            = @DI\Inject("claroline.manager.role_manager"),
     *     "strictEventDispatcher"  = @DI\Inject("claroline.event.event_dispatcher"),
     *     "toolManager"            = @DI\Inject("claroline.manager.tool_manager"),
     *     "translator"             = @DI\Inject("translator"),
     *     "validator"              = @DI\Inject("validator"),
     *     "workspaceManager"       = @DI\Inject("claroline.manager.workspace_manager"),
     *     "uploadsDirectory"       = @DI\Inject("%claroline.param.uploads_directory%"),
     *     "container"              = @DI\Inject("service_container")
     * })
     */
    public function __construct(
        $personalWsTemplateFile,
        MailManager $mailManager,
        ObjectManager $objectManager,
        PagerFactory $pagerFactory,
        PlatformConfigurationHandler $platformConfigHandler,
        RoleManager $roleManager,
        StrictDispatcher $strictEventDispatcher,
        ToolManager $toolManager,
        Translator $translator,
        ValidatorInterface $validator,
        WorkspaceManager $workspaceManager,
        $uploadsDirectory,
        ContainerInterface $container
    )
    {
        $this->userRepo               = $objectManager->getRepository('ClarolineCoreBundle:User');
        $this->roleManager            = $roleManager;
        $this->workspaceManager       = $workspaceManager;
        $this->toolManager            = $toolManager;
        $this->strictEventDispatcher  = $strictEventDispatcher;
        $this->personalWsTemplateFile = $personalWsTemplateFile . "default.zip";
        $this->translator             = $translator;
        $this->platformConfigHandler  = $platformConfigHandler;
        $this->pagerFactory           = $pagerFactory;
        $this->objectManager          = $objectManager;
        $this->mailManager            = $mailManager;
        $this->validator              = $validator;
        $this->uploadsDirectory       = $uploadsDirectory;
        $this->container              = $container;
        $this->resourceNodeRepo       = $objectManager->getRepository('ClarolineCoreBundle:Resource\ResourceNode');
    }

    /**
     * Create a user.
     * Its basic properties (name, username,... ) must already be set.
     *
     * @param \Claroline\CoreBundle\Entity\User $user
     *
     * @return \Claroline\CoreBundle\Entity\User
     */
    public function createUser(User $user, $wsGuid = null)
    {
        $this->objectManager->startFlushSuite();
        $this->setPersonalWorkspace($user, $wsGuid);
        $user->setPublicUrl($this->generatePublicUrl($user));
        $this->toolManager->addRequiredToolsToUser($user);
        $this->roleManager->setRoleToRoleSubject($user, PlatformRoles::USER);
        $this->objectManager->persist($user);
        $this->strictEventDispatcher->dispatch('log', 'Log\LogUserCreate', array($user));
        $this->objectManager->endFlushSuite();

        if ($this->mailManager->isMailerAvailable()) {
            $this->mailManager->sendCreationMessage($user);
        }

        return $user;
    }

    /**
     * Rename a user.
     *
     * @param User $user
     * @param $username
     */
    public function rename(User $user, $username)
    {
        $user->setUsername($username);
        $personalWorkspaceName = $this->translator->trans('personal_workspace', array(), 'platform') . $user->getUsername();
        $pws = $user->getPersonalWorkspace();
        $this->workspaceManager->rename($pws, $personalWorkspaceName);
        $this->objectManager->persist($user);
        $this->objectManager->flush();
    }

    public function setIsMailNotified(User $user, $isNotified)
    {
        $user->setIsMailNotified($isNotified);
        $this->objectManager->persist($user);
        $this->objectManager->flush();
    }

    /**
     * Removes a user.
     *
     * @param \Claroline\CoreBundle\Entity\User $user
     */
    public function deleteUser(User $user)
    {
        if ($this->container->get('security.context')->getToken()->getUser()->getId() === $user->getId()) {
            throw new \Exception('A user cannot delete himself');
        }

        //soft delete~
        $user->setMail('mail#' . $user->getId());
        $user->setFirstName('firstname#' . $user->getId());
        $user->setLastName('lastname#' . $user->getId());
        $user->setPlainPassword(uniqid());
        $user->setUsername('username#' . $user->getId());
        $user->setIsEnabled(false);

        // keeping the user's workspace with its original code
        // would prevent creating a user with the same username
        // todo: workspace deletion should be an option
        $ws = $user->getPersonalWorkspace();

        if ($ws) {
            $ws->setCode($ws->getCode() . '#deleted_user#' . $user->getId());
            $ws->setDisplayable(false);
            $this->objectManager->persist($ws);
        }

        $this->objectManager->persist($user);
        $this->objectManager->flush();

        $this->strictEventDispatcher->dispatch('log', 'Log\LogUserDelete', array($user));
        $this->strictEventDispatcher->dispatch('delete_user', 'DeleteUser', array($user));
    }

    /**
     * Create a user.
     * Its basic properties (name, username,... ) must already be set.
     * This user will have the additional role  $roleName.
     * $roleName must already exists.
     *
     * @param \Claroline\CoreBundle\Entity\User $user
     * @param string                            $roleName
     *
     * @return \Claroline\CoreBundle\Entity\User
     */
    public function createUserWithRole(User $user, $roleName)
    {
        $this->objectManager->startFlushSuite();
        $this->createUser($user);
        $this->roleManager->setRoleToRoleSubject($user, $roleName);
        $this->objectManager->endFlushSuite();

        return $user;
    }

    /**
     * Create a user.
     * Its basic properties (name, username,... ) must already be set.
     * This user will have the additional roles $roles.
     * These roles must already exists.
     *
     * @param \Claroline\CoreBundle\Entity\User $user
     * @param \Doctrine\Common\Collections\ArrayCollection $roles
     */
    public function insertUserWithRoles(User $user, ArrayCollection $roles)
    {
        $this->objectManager->startFlushSuite();
        $this->createUser($user);
        foreach ($roles as $role) {
            $validated = $this->roleManager->validateRoleInsert($user, $role);

            if (!$validated) {
                throw new Exception\AddRoleException();
            }
        }

        $this->roleManager->associateRoles($user, $roles);
        $this->objectManager->endFlushSuite();
    }

    /**
     * Import users from an array.
     * There is the array format:
     * @todo some batch processing
     *
     * array(
     *     array(firstname, lastname, username, pwd, email, code, phone),
     *     array(firstname2, lastname2, username2, pwd2, email2, code2, phone2),
     *     array(firstname3, lastname3, username3, pwd3, email3, code3, phone3),
     * )
     *
     * @param array $users
     *
     * @return array
     */
    public function importUsers(array $users)
    {
        $roleUser = $this->roleManager->getRoleByName('ROLE_USER');
        $max = $roleUser->getMaxUsers();
        $total = $this->countUsersByRoleIncludingGroup($roleUser);

        if ($total + count($users) > $max) {
            throw new AddRoleException();
        }

        $lg = $this->platformConfigHandler->getParameter('locale_language');
        $this->objectManager->startFlushSuite();

        foreach ($users as $user) {
            $firstName = $user[0];
            $lastName = $user[1];
            $username = $user[2];
            $pwd = $user[3];
            $email = $user[4];
            $code = isset($user[5])? $user[5] : null;
            $phone = isset($user[6])? $user[6] : null;

            $newUser = $this->objectManager->factory('Claroline\CoreBundle\Entity\User');
            $newUser->setFirstName($firstName);
            $newUser->setLastName($lastName);
            $newUser->setUsername($username);
            $newUser->setPlainPassword($pwd);
            $newUser->setMail($email);
            $newUser->setAdministrativeCode($code);
            $newUser->setPhone($phone);
            $newUser->setLocale($lg);
            $this->createUser($newUser);
        }

        $this->objectManager->endFlushSuite();
    }

    /**
     * Creates the personal workspace of a user.
     *
     * @param \Claroline\CoreBundle\Entity\User $user
     */
    public function setPersonalWorkspace(User $user, $wsGuid = null)
    {
        $config = Configuration::fromTemplate($this->personalWsTemplateFile);
        $locale = $this->platformConfigHandler->getParameter('locale_language');
        $this->translator->setLocale($locale);
        $personalWorkspaceName = $this->translator->trans('personal_workspace', array(), 'platform') . $user->getUsername();
        $config->setWorkspaceName($personalWorkspaceName);
        $config->setWorkspaceCode($user->getUsername());
        var_dump($wsGuid);
        if ($wsGuid) $config->setGuid($wsGuid);
        $workspace = $this->workspaceManager->create($config, $user);
        $user->setPersonalWorkspace($workspace);
        $this->objectManager->persist($user);
        $this->objectManager->flush();
    }

    /**
     * Sets an array of platform role to a user.
     *
     * @param \Claroline\CoreBundle\Entity\User $user
     * @param ArrayCollection                   $roles
     */
    public function setPlatformRoles(User $user, $roles)
    {
        $this->roleManager->resetRoles($user);
        $this->roleManager->associateRoles($user, $roles);
    }

    /**
     * Serialize a user.
     *
     * @param array $users
     *
     * @return array
     */
    public function convertUsersToArray(array $users)
    {
        $content = array();
        $i = 0;

        foreach ($users as $user) {
            $content[$i]['id'] = $user->getId();
            $content[$i]['username'] = $user->getUsername();
            $content[$i]['lastname'] = $user->getLastName();
            $content[$i]['firstname'] = $user->getFirstName();
            $content[$i]['administrativeCode'] = $user->getAdministrativeCode();

            $rolesString = '';
            $roles = $user->getEntityRoles();
            $rolesCount = count($roles);
            $j = 0;

            foreach ($roles as $role) {
                $rolesString .= "{$this->translator->trans($role->getTranslationKey(), array(), 'platform')}";

                if ($j < $rolesCount - 1) {
                    $rolesString .= ' ,';
                }
                $j++;
            }
            $content[$i]['roles'] = $rolesString;
            $i++;
        }

        return $content;
    }

    /**
     * @param type $username
     *
     * @return User
     */
    public function getUserByUsername($username)
    {
        try {
            $user = $this->userRepo->loadUserByUsername($username);
        } catch (\Exception $e)
        {
            $user = null;
        }
        return $user;
    }

    /**
     * @param \Symfony\Component\Security\Core\User\UserInterface $user
     *
     * @return User
     */
    public function refreshUser(UserInterface $user)
    {
        return $this->userRepo->refreshUser($user);
    }

    /**
     * @param integer $page
     * @param integer $max
     * @param string  $orderedBy
     * @param string  $order
     *
     * @return \Pagerfanta\Pagerfanta;
     */
    public function getAllUsers($page, $max = 20, $orderedBy = 'id', $order = null)
    {
        $query = $this->userRepo->findAll(false, $orderedBy, $order);

        return $this->pagerFactory->createPager($query, $page, $max);
    }

    /**
     * @param string  $search
     * @param integer $page
     * @param integer $max
     *
     * @return \Pagerfanta\Pagerfanta;
     */
    public function getAllUsersBySearch($page, $search, $max = 20)
    {
        $users = $this->userRepo->findAllUserBySearch($search);

        return $this->pagerFactory->createPagerFromArray($users, $page, $max);
    }

    /**
     * @param string  $search
     * @param integer $page
     * @param integer $max
     * @param string  $orderedBy
     *
     * @return \Pagerfanta\Pagerfanta;
     */
    public function getUsersByName($search, $page, $max = 20, $orderedBy = 'id')
    {
        $query = $this->userRepo->findByName($search, false, $orderedBy);

        return $this->pagerFactory->createPager($query, $page, $max);
    }

    /**
     * @param \Claroline\CoreBundle\Entity\Group $group
     * @param integer                            $page
     * @param integer                            $max
     * @param string                             $orderedBy
     *
     * @return \Pagerfanta\Pagerfanta;
     */
    public function getUsersByGroup(Group $group, $page, $max = 20, $orderedBy = 'id', $order = null)
    {
        $query = $this->userRepo->findByGroup($group, false, $orderedBy, $order);

        return $this->pagerFactory->createPager($query, $page, $max);
    }

    /**
     * @param \Claroline\CoreBundle\Entity\Group $group
     *
     * @return User[]
     */
    public function getUsersByGroupWithoutPager(Group $group)
    {
        return $this->userRepo->findByGroup($group);
    }

    /**
     * @param Workspace $workspace
     *
     * @return User[]
     */
    public function getByWorkspaceWithUsersFromGroup(Workspace $workspace)
    {
        return $this->userRepo->findByWorkspaceWithUsersFromGroup($workspace);
    }

    /**
     *
     * @param string                             $search
     * @param \Claroline\CoreBundle\Entity\Group $group
     * @param integer                            $page
     * @param integer                            $max
     * @param string                             $orderedBy
     *
     * @return \Pagerfanta\Pagerfanta
     */
    public function getUsersByNameAndGroup($search, Group $group, $page, $max = 20, $orderedBy = 'id')
    {
        $query = $this->userRepo->findByNameAndGroup($search, $group, false, $orderedBy);

        return $this->pagerFactory->createPager($query, $page, $max);
    }

    /**
     * @param \Claroline\CoreBundle\Entity\Workspace\Workspace[] $workspaces
     * @param integer                                                    $page
     * @param integer                                                    $max
     *
     * @return \Pagerfanta\Pagerfanta
     */
    public function getUsersByWorkspaces(array $workspaces, $page, $max = 20)
    {
        $query = $this->userRepo->findUsersByWorkspaces($workspaces, false);

        return $this->pagerFactory->createPager($query, $page, $max);
    }

    /**
     * @param \Claroline\CoreBundle\Entity\Workspace\Workspace[] $workspaces
     * @param integer                                                    $page
     * @param string                                                     $search
     * @param integer                                                    $max
     *
     * @return \Pagerfanta\Pagerfanta
     */
    public function getUsersByWorkspacesAndSearch(
        array $workspaces,
        $page,
        $search,
        $max = 20
    )
    {
        $users = $this->userRepo
            ->findUsersByWorkspacesAndSearch($workspaces, $search);

        return $this->pagerFactory->createPagerFromArray($users, $page, $max);
    }

    /**
     * @param \Claroline\CoreBundle\Entity\Workspace\Workspace $workspace
     * @param string                                                   $search
     * @param integer                                                  $page
     * @param integer                                                  $max
     *
     * @return \Pagerfanta\Pagerfanta
     */
    public function getAllUsersByWorkspaceAndName(Workspace $workspace, $search, $page, $max = 20)
    {
        $query = $this->userRepo->findAllByWorkspaceAndName($workspace, $search, false);

        return $this->pagerFactory->createPager($query, $page, $max);
    }

    /**
     * @param \Claroline\CoreBundle\Entity\Group $group
     * @param integer                            $page
     * @param integer                            $max
     * @param string                             $orderedBy
     *
     * @return \Pagerfanta\Pagerfanta
     */
    public function getGroupOutsiders(Group $group, $page, $max = 20, $orderedBy = 'id')
    {
        $query = $this->userRepo->findGroupOutsiders($group, false, $orderedBy);

        return $this->pagerFactory->createPager($query, $page, $max);
    }

    /**
     * @param \Claroline\CoreBundle\Entity\Group $group
     * @param integer                            $page
     * @param string                             $search
     * @param integer                            $max
     * @param string                             $orderedBy
     *
     * @return \Pagerfanta\Pagerfanta
     */
    public function getGroupOutsidersByName(Group $group, $page, $search, $max = 20, $orderedBy = 'id')
    {
        $query = $this->userRepo->findGroupOutsidersByName($group, $search, false, $orderedBy);

        return $this->pagerFactory->createPager($query, $page, $max);
    }

    /**
     * @return integer
     */
    public function getNbUsers()
    {
        return $this->userRepo->count();
    }

    public function countUsersForPlatformRoles()
    {
        $roles = $this->roleManager->getAllPlatformRoles();
        $usersInRoles = array();
        $usersInRoles['user_accounts'] = 0;
        foreach ($roles as $role) {
            $restrictionRoleNames = null;
            if ($role->getName() === 'ROLE_USER') {
                $restrictionRoleNames = array('ROLE_WS_CREATOR', 'ROLE_ADMIN');
            } elseif ($role->getName() === 'ROLE_WS_CREATOR') {
                $restrictionRoleNames = array('ROLE_ADMIN');
            }
            $usersInRoles[$role->getTranslationKey()] = intval(
                $this->userRepo->countUsersByRole($role, $restrictionRoleNames)
            );
            $usersInRoles['user_accounts'] += $usersInRoles[$role->getTranslationKey()];
        }

        return $usersInRoles;
    }

    /**
     * @param integer[] $ids
     *
     * @return User[]
     */
    public function getUsersByIds(array $ids)
    {
        return $this->objectManager->findByIds('Claroline\CoreBundle\Entity\User', $ids);
    }

    /**
     * @param integer $max
     *
     * @return User[]
     */
    public function getUsersEnrolledInMostWorkspaces($max)
    {
        return $this->userRepo->findUsersEnrolledInMostWorkspaces($max);
    }

    /**
     * @param integer $max
     *
     * @return User[]
     */
    public function getUsersOwnersOfMostWorkspaces($max)
    {
        return $this->userRepo->findUsersOwnersOfMostWorkspaces($max);
    }

    /**
     * @param integer $userId
     *
     * @return User
     */
    public function getUserById($userId)
    {
        return $this->userRepo->find($userId);
    }

    /**
     * @param Role[] $roles
     * @param integer $page
     * @param integer $max
     * @param string $orderedBy
     *
     * @param null $order
     * @return \Pagerfanta\Pagerfanta
     */
    public function getByRolesIncludingGroups(array $roles, $page = 1, $max = 20, $orderedBy = 'id', $order= null)
    {
        $res = $this->userRepo->findByRolesIncludingGroups($roles, true, $orderedBy, $order);

        return $this->pagerFactory->createPager($res, $page, $max);
    }

    /**
     * @param Role[]  $roles
     * @param integer $page
     * @param integer $max
     *
     * @return \Pagerfanta\Pagerfanta
     */
    public function getUsersByRolesIncludingGroups(
        array $roles,
        $page = 1,
        $max = 20,
        $executeQuery = true
    )
    {
        $users = $this->userRepo
            ->findUsersByRolesIncludingGroups($roles, $executeQuery);

        return $this->pagerFactory->createPagerFromArray($users, $page, $max);
    }

    /**
     * @param Role[]  $roles
     * @param string  $search
     * @param integer $page
     * @param integer $max
     * @param string  $orderedBy
     *
     * @return \Pagerfanta\Pagerfanta
     */
    public function getByRolesAndNameIncludingGroups(array $roles, $search, $page = 1, $max = 20, $orderedBy = 'id', $direction = null)
    {
        $res = $this->userRepo->findByRolesAndNameIncludingGroups($roles, $search, true, $orderedBy, $direction);

        return $this->pagerFactory->createPager($res, $page, $max);
    }

    /**
     * @param Role[]  $roles
     * @param integer $page
     * @param integer $max
     *
     * @return \Pagerfanta\Pagerfanta
     */
    public function getUsersByRoles(array $roles, $page = 1, $max = 20)
    {
        $res = $this->userRepo->findByRoles($roles, true);

        return $this->pagerFactory->createPager($res, $page, $max);
    }

    /**
     * @param string $email
     *
     * @return User
     */
    public function getUserByEmail($email)
    {
        return $this->userRepo->findOneByMail($email);
    }

    /**
     * @todo Please describe me. I couldn't find findOneByResetPasswordHash.
     *
     * @param string $resetPassword
     *
     * @return User
     */
    public function getResetPasswordHash($resetPassword)
    {
        return $this->userRepo->findOneByResetPasswordHash($resetPassword);
    }

    /**
     * @param \Claroline\CoreBundle\Entity\User $user
     */
    public function uploadAvatar(User $user)
    {
        if (null !== $user->getPictureFile()) {
            if (!is_writable($pictureDir = $this->uploadsDirectory.'/pictures/')) {
                throw new \Exception("{$pictureDir} is not writable");
            }

            $user->setPicture(
                sha1(
                    $user->getPictureFile()->getClientOriginalName()
                    . $user->getId())
                    . '.'
                    . $user->getPictureFile()->guessExtension()
            );
            $user->getPictureFile()->move($pictureDir, $user->getPicture());
        }

    }

    /**
     * Set the user locale.
     *
     * @param \Claroline\CoreBundle\Entity\User $user
     * @param String                            $locale Language with format en, fr, es, etc.
     */
    public function setLocale(User $user, $locale = 'en')
    {
        $user->setLocale($locale);
        $this->objectManager->persist($user);
        $this->objectManager->flush();
    }

    public function toArrayForPicker($users)
    {
        $resultArray = array();

        $resultArray['users'] = array();
        if (count($users)>0) {
            foreach ($users as $user) {
                $userArray = array();
                $userArray['id'] = $user->getId();
                $userArray['name'] = $user->getFirstName()." ".$user->getLastName();
                $userArray['mail'] = $user->getMail();
                $userArray['avatar'] = $user->getPicture();
                array_push($resultArray['users'], $userArray);
            }
        }

        return $resultArray;
    }

    /**
     * @param User $user
     * @param int  $try
     *
     * @return string
     */
    public function generatePublicUrl(User $user, $try = 0)
    {
        $publicUrl = $user->getFirstName() . '.' . $user->getLastName();
        $publicUrl = strtolower(str_replace(' ', '-', $publicUrl));

        if (0 < $try) {
            $publicUrl .= $try;
        }

        $searchedUsers = $this->objectManager->getRepository('ClarolineCoreBundle:User')->findOneByPublicUrl($publicUrl);
        if (null !== $searchedUsers) {
            $publicUrl = $this->generatePublicUrl($user, ++$try);
        }

        return $publicUrl;
    }

    public function countUsersByRoleIncludingGroup(Role $role)
    {
        return $this->objectManager->getRepository('ClarolineCoreBundle:User')->countUsersByRoleIncludingGroup($role);
    }

    public function countUsersOfGroup(Group $group)
    {
        return $this->userRepo->countUsersOfGroup($group);
    }

    public function setUserInitDate(User $user)
    {
        $accountDuration = $this->platformConfigHandler->getParameter('account_duration');
        $expirationDate = new \DateTime();

        ($accountDuration === null) ?
            $expirationDate->setDate(2100, 1, 1):
            $expirationDate->add(new \DateInterval('P' . $accountDuration . 'D'));

        $user->setExpirationDate($expirationDate);
        $user->setInitDate(new \DateTime());
        $this->objectManager->persist($user);
        $this->objectManager->flush();
    }
    
    public function getUserAsTab($user)
    {        
        $wsResnode = $this->resourceNodeRepo->findWorkspaceRoot($user->getPersonalWorkspace());
        
        return array(
                "first_name" => $user->getFirstName(),
                "last_name" => $user->getLastName(),
                'username' => $user->getUsername(),
                'mail' => $user->getMail(),
                'hasAceptedTerms' => $user->hasAcceptedTerms(),
                'token' => $user->getExchangeToken(),
                'ws_perso' => $user->getPersonalWorkspace()->getGuid(),
                'ws_resnode' => $wsResnode->getNodeHashName(),
                'admin' => $user->isAdmin()
        );
    }
}
