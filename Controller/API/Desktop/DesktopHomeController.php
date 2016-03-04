<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Controller\API\Desktop;

use Claroline\CoreBundle\Entity\Home\HomeTabConfig;
use Claroline\CoreBundle\Library\Security\Utilities;
use Claroline\CoreBundle\Manager\HomeTabManager;
use Claroline\CoreBundle\Manager\RoleManager;
use Claroline\CoreBundle\Manager\UserManager;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @NamePrefix("api_")
 */
class DesktopHomeController extends FOSRestController
{
    private $authorization;
    private $homeTabManager;
    private $roleManager;
    private $tokenStorage;
    private $userManager;
    private $utils;

    /**
     * @DI\InjectParams({
     *     "authorization"  = @DI\Inject("security.authorization_checker"),
     *     "homeTabManager" = @DI\Inject("claroline.manager.home_tab_manager"),
     *     "roleManager"    = @DI\Inject("claroline.manager.role_manager"),
     *     "tokenStorage"   = @DI\Inject("security.token_storage"),
     *     "userManager"    = @DI\Inject("claroline.manager.user_manager"),
     *     "utils"          = @DI\Inject("claroline.security.utilities")
     * })
     */
    public function __construct(
        AuthorizationCheckerInterface $authorization,
        HomeTabManager $homeTabManager,
        RoleManager $roleManager,
        TokenStorageInterface $tokenStorage,
        UserManager $userManager,
        Utilities $utils
    )
    {
        $this->authorization = $authorization;
        $this->homeTabManager = $homeTabManager;
        $this->roleManager = $roleManager;
        $this->tokenStorage = $tokenStorage;
        $this->userManager = $userManager;
        $this->utils = $utils;
    }

    /**
     * @View(serializerGroups={"api_home_tab"})
     * @ApiDoc(
     *     description="Returns list of desktop home tabs",
     *     views = {"desktop_home"}
     * )
     */
    public function getDesktopHomeTabsAction()
    {
        $token = $this->tokenStorage->getToken();
        $user = $token->getUser();

        if ($user === '.anon') {

            throw new AccessDeniedException();
        } else {
            $options = $this->userManager->getUserOptions($user);
            $desktopHomeDatas = array(
                'tabsAdmin' => array(),
                'tabsUser' => array(),
                'tabsWorkspace' => array()
            );
            $roleNames = $this->utils->getRoles($token);
            $adminHomeTabConfigs = $this->homeTabManager
                ->generateAdminHomeTabConfigsByUser($user, $roleNames);
            $visibleAdminHomeTabConfigs = $this->homeTabManager
                ->filterVisibleHomeTabConfigs($adminHomeTabConfigs);
            $userHomeTabConfigs = $this->homeTabManager
                ->getVisibleDesktopHomeTabConfigsByUser($user);
            $workspaceUserHTCs = $this->homeTabManager
                ->getVisibleWorkspaceUserHTCsByUser($user);

            foreach ($visibleAdminHomeTabConfigs as $htc) {
                $tab = $htc->getHomeTab();
                $details = $htc->getDetails();
                $color = isset($details['color']) ? $details['color'] : null;
                $desktopHomeDatas['tabsAdmin'][] = array(
                    'configId' => $htc->getId(),
                    'locked' => $htc->isLocked(),
                    'tabOrder' => $htc->getTabOrder(),
                    'type' => $htc->getType(),
                    'visible' => $htc->isVisible(),
                    'tabId' => $tab->getId(),
                    'tabName' => $tab->getName(),
                    'tabType' => $tab->getType(),
                    'tabIcon' => $tab->getIcon(),
                    'color' => $color
                );
            }

            foreach ($userHomeTabConfigs as $htc) {
                $tab = $htc->getHomeTab();
                $details = $htc->getDetails();
                $color = isset($details['color']) ? $details['color'] : null;
                $desktopHomeDatas['tabsUser'][] = array(
                    'configId' => $htc->getId(),
                    'locked' => $htc->isLocked(),
                    'tabOrder' => $htc->getTabOrder(),
                    'type' => $htc->getType(),
                    'visible' => $htc->isVisible(),
                    'tabId' => $tab->getId(),
                    'tabName' => $tab->getName(),
                    'tabType' => $tab->getType(),
                    'tabIcon' => $tab->getIcon(),
                    'color' => $color
                );
            }

            foreach ($workspaceUserHTCs as $htc) {
                $tab = $htc->getHomeTab();
                $details = $htc->getDetails();
                $color = isset($details['color']) ? $details['color'] : null;
                $desktopHomeDatas['tabsWorkspace'][] = array(
                    'configId' => $htc->getId(),
                    'locked' => $htc->isLocked(),
                    'tabOrder' => $htc->getTabOrder(),
                    'type' => $htc->getType(),
                    'visible' => $htc->isVisible(),
                    'tabId' => $tab->getId(),
                    'tabName' => $tab->getName(),
                    'tabType' => $tab->getType(),
                    'tabIcon' => $tab->getIcon(),
                    'color' => $color
                );
            }
            $desktopHomeDatas['editionMode'] = $options->getDesktopMode() === 1;
            $desktopHomeDatas['isHomeLocked'] = $this->roleManager->isHomeLocked($user);

            return $desktopHomeDatas;
        }
    }

    /**
     * @View(serializerGroups={"api_home_tab"})
     * @ApiDoc(
     *     description="Switch desktop home edition mode",
     *     views = {"desktop_home"}
     * )
     */
    public function putDesktopHomeEditionModeToggleAction()
    {
        $user = $this->tokenStorage->getToken()->getUser();

        if ($user === '.anon') {

            throw new AccessDeniedException();
        }
        $options = $this->userManager->switchDesktopMode($user);

        return new JsonResponse($options->getDesktopMode(), 200);
    }

    /**
     * @View(serializerGroups={"api_home_tab"})
     * @ApiDoc(
     *     description="Delete pinned workspace home tab",
     *     views = {"desktop_home"}
     * )
     */
    public function deletePinnedWorkspaceHomeTabAction(HomeTabConfig $htc)
    {
        $authenticatedUser = $this->tokenStorage->getToken()->getUser();
        $user = $htc->getUser();
        $type = $htc->getType();

        if ($authenticatedUser !== $user || $type !== 'workspace_user') {

            throw new AccessDeniedException($type);
        }
        $tab = $htc->getHomeTab();
        $details = $htc->getDetails();
        $color = isset($details['color']) ? $details['color'] : null;
        $htcDatas = array(
            'configId' => $htc->getId(),
            'locked' => $htc->isLocked(),
            'tabOrder' => $htc->getTabOrder(),
            'type' => $htc->getType(),
            'visible' => $htc->isVisible(),
            'tabId' => $tab->getId(),
            'tabName' => $tab->getName(),
            'tabType' => $tab->getType(),
            'tabIcon' => $tab->getIcon(),
            'color' => $color
        );
        $this->homeTabManager->deleteHomeTabConfig($htc);

        return new JsonResponse($htcDatas, 200);
    }
}
