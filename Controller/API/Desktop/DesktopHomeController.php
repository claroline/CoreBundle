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

use Claroline\CoreBundle\Entity\Home\HomeTab;
use Claroline\CoreBundle\Entity\Home\HomeTabConfig;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Event\Log\LogHomeTabAdminUserEditEvent;
use Claroline\CoreBundle\Event\Log\LogHomeTabUserCreateEvent;
use Claroline\CoreBundle\Event\Log\LogHomeTabUserDeleteEvent;
use Claroline\CoreBundle\Event\Log\LogHomeTabUserEditEvent;
use Claroline\CoreBundle\Event\Log\LogHomeTabWorkspaceUnpinEvent;
use Claroline\CoreBundle\Event\StrictDispatcher;
use Claroline\CoreBundle\Form\HomeTabType;
use Claroline\CoreBundle\Library\Security\Utilities;
use Claroline\CoreBundle\Manager\ApiManager;
use Claroline\CoreBundle\Manager\HomeTabManager;
use Claroline\CoreBundle\Manager\RoleManager;
use Claroline\CoreBundle\Manager\UserManager;
use Claroline\CoreBundle\Manager\WidgetManager;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @NamePrefix("api_")
 */
class DesktopHomeController extends FOSRestController
{
    private $apiManager;
    private $authorization;
    private $eventDispatcher;
    private $eventStrictDispatcher;
    private $homeTabManager;
    private $request;
    private $roleManager;
    private $tokenStorage;
    private $userManager;
    private $utils;
    private $widgetManager;

    /**
     * @DI\InjectParams({
     *     "apiManager"            = @DI\Inject("claroline.manager.api_manager"),
     *     "authorization"         = @DI\Inject("security.authorization_checker"),
     *     "eventDispatcher"       = @DI\Inject("event_dispatcher"),
     *     "eventStrictDispatcher" = @DI\Inject("claroline.event.event_dispatcher"),
     *     "homeTabManager"        = @DI\Inject("claroline.manager.home_tab_manager"),
     *     "request"               = @DI\Inject("request"),
     *     "roleManager"           = @DI\Inject("claroline.manager.role_manager"),
     *     "tokenStorage"          = @DI\Inject("security.token_storage"),
     *     "userManager"           = @DI\Inject("claroline.manager.user_manager"),
     *     "utils"                 = @DI\Inject("claroline.security.utilities"),
     *     "widgetManager"         = @DI\Inject("claroline.manager.widget_manager")
     * })
     */
    public function __construct(
        ApiManager $apiManager,
        AuthorizationCheckerInterface $authorization,
        EventDispatcherInterface $eventDispatcher,
        StrictDispatcher $eventStrictDispatcher,
        HomeTabManager $homeTabManager,
        Request $request,
        RoleManager $roleManager,
        TokenStorageInterface $tokenStorage,
        UserManager $userManager,
        Utilities $utils,
        WidgetManager $widgetManager
    )
    {
        $this->apiManager = $apiManager;
        $this->authorization = $authorization;
        $this->eventDispatcher = $eventDispatcher;
        $this->eventStrictDispatcher = $eventStrictDispatcher;
        $this->homeTabManager = $homeTabManager;
        $this->request = $request;
        $this->roleManager = $roleManager;
        $this->tokenStorage = $tokenStorage;
        $this->userManager = $userManager;
        $this->utils = $utils;
        $this->widgetManager = $widgetManager;
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
            $desktopHomeDatas['editionMode'] = $options->getDesktopMode() === 1;
            $desktopHomeDatas['isHomeLocked'] = $this->roleManager->isHomeLocked($user);
            $visibleAdminHomeTabConfigs = array();
            $userHomeTabConfigs = array();
            $workspaceUserHTCs = array();
            $roleNames = $this->utils->getRoles($token);

            if ($desktopHomeDatas['isHomeLocked']) {
                $visibleAdminHomeTabConfigs = $this->homeTabManager
                    ->getVisibleAdminDesktopHomeTabConfigsByRoles($roleNames);
                $workspaceUserHTCs = $this->homeTabManager
                    ->getVisibleWorkspaceUserHTCsByUser($user);
            } else {
                $adminHomeTabConfigs = $this->homeTabManager
                    ->generateAdminHomeTabConfigsByUser($user, $roleNames);
                $visibleAdminHomeTabConfigs = $this->homeTabManager
                    ->filterVisibleHomeTabConfigs($adminHomeTabConfigs);
                $userHomeTabConfigs = $this->homeTabManager
                    ->getVisibleDesktopHomeTabConfigsByUser($user);
                $workspaceUserHTCs = $this->homeTabManager
                    ->getVisibleWorkspaceUserHTCsByUser($user);
            }

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

            return $desktopHomeDatas;
        }
    }

    /**
     * @View(serializerGroups={"api_home_tab"})
     * @ApiDoc(
     *     description="Returns list of widgets of a home tab",
     *     views = {"desktop_home"}
     * )
     */
    public function getDesktopHomeTabWidgetsAction(HomeTab $homeTab)
    {
        $user = $this->tokenStorage->getToken()->getUser();
        $isVisibleHomeTab = $this->homeTabManager
            ->checkHomeTabVisibilityForConfigByUser($homeTab, $user);
        $isLockedHomeTab = $this->homeTabManager->checkHomeTabLock($homeTab);
        $isWorkspace = false;
        $configs = array();
        $widgets = array();
        $widgetsDatas = array(
            'isLockedHomeTab' => $isLockedHomeTab,
            'initWidgetsPosition' => false,
            'widgets' => array()
        );

        if ($isVisibleHomeTab) {

            if ($homeTab->getType() === 'admin_desktop') {
                $adminConfigs = $this->homeTabManager->getAdminWidgetConfigs($homeTab);

                if (!$isLockedHomeTab) {
                    $userWidgetsConfigs = $this->homeTabManager
                        ->getWidgetConfigsByUser($homeTab, $user);
                } else {
                    $userWidgetsConfigs = array();
                }

                foreach ($adminConfigs as $adminConfig) {

                    if ($adminConfig->isLocked()) {
                        if ($adminConfig->isVisible()) {
                            $configs[] = $adminConfig;
                        }
                    } else {
                        $existingWidgetConfig = $this->homeTabManager
                            ->getUserAdminWidgetHomeTabConfig(
                                $homeTab,
                                $adminConfig->getWidgetInstance(),
                                $user
                            );
                        if (count($existingWidgetConfig) === 0) {
                            $newWHTC = new WidgetHomeTabConfig();
                            $newWHTC->setHomeTab($homeTab);
                            $newWHTC->setWidgetInstance($adminConfig->getWidgetInstance());
                            $newWHTC->setUser($user);
                            $newWHTC->setWidgetOrder($adminConfig->getWidgetOrder());
                            $newWHTC->setVisible($adminConfig->isVisible());
                            $newWHTC->setLocked(false);
                            $newWHTC->setType('admin_desktop');
                            $this->homeTabManager->insertWidgetHomeTabConfig($newWHTC);
                            $configs[] = $newWHTC;
                        } else {
                            $configs[] = $existingWidgetConfig[0];
                        }
                    }
                }

                foreach ($userWidgetsConfigs as $userWidgetsConfig) {
                    $configs[] = $userWidgetsConfig;
                }
            } elseif ($homeTab->getType() === 'desktop') {
                $configs = $this->homeTabManager->getWidgetConfigsByUser($homeTab, $user);
            } elseif ($homeTab->getType() === 'workspace') {
                $workspace = $homeTab->getWorkspace();
                $widgetsDatas['isLockedHomeTab'] = true;
                $isWorkspace = true;
                $configs = $this->homeTabManager->getWidgetConfigsByWorkspace(
                    $homeTab,
                    $workspace
                );
            }

            $wdcs = $isWorkspace ?
                $this->widgetManager->generateWidgetDisplayConfigsForWorkspace(
                    $workspace,
                    $configs
                ) :
                $this->widgetManager->generateWidgetDisplayConfigsForUser(
                    $user,
                    $configs
                );

            foreach ($wdcs as $wdc) {

                if ($wdc->getRow() === -1 || $wdc->getColumn() === -1) {
                    $widgetsDatas['initWidgetsPosition'] = true;
                    break;
                }
            }

            foreach ($configs as $config) {
                $widgetDatas = array();
//                $eventName = 'widget_' . $config->getWidgetInstance()->getWidget()->getName();
                $widgetInstance = $config->getWidgetInstance();
                $widget = $widgetInstance->getWidget();
                $widgetInstanceId = $widgetInstance->getId();
                $widgetDatas['widgetId'] = $widget->getId();
                $widgetDatas['widgetName'] = $widget->getName();
                $widgetDatas['configId'] = $config->getId();
//                $event = $this->eventDispatcher->dispatch(
//                    $eventName,
//                    new \Claroline\CoreBundle\Event\DisplayWidgetEvent($widgetInstance)
//                );
//                $event = $this->eventStrictDispatcher->dispatch(
//                    "widget_{$config->getWidgetInstance()->getWidget()->getName()}",
//                    'DisplayWidget',
//                    array($config->getWidgetInstance())
//                );
//                throw new \Exception(var_dump($event));
//
////                $widget['config'] = $config;
//                $widget['content'] = $event->getContent();
                $widgetDatas['configurable'] = $config->isLocked() !== true
                    && $config->getWidgetInstance()->getWidget()->isConfigurable();
                $widgetDatas['type'] = $config->getType();
                $widgetDatas['instanceId'] = $widgetInstanceId;
                $widgetDatas['instanceName'] = $widgetInstance->getName();
                $widgetDatas['instanceIcon'] = $widgetInstance->getIcon();
                $widgetDatas['row'] = $wdcs[$widgetInstanceId]->getRow();
                $widgetDatas['column'] = $wdcs[$widgetInstanceId]->getColumn();
                $widgetDatas['height'] = $wdcs[$widgetInstanceId]->getHeight();
                $widgetDatas['width'] = $wdcs[$widgetInstanceId]->getWidth();
                $widgetDatas['color'] = $wdcs[$widgetInstanceId]->getColor();
                $widgets[] = $widgetDatas;
            }
            $widgetsDatas['widgets'] = $widgets;
        }

        return new JsonResponse($widgetsDatas, 200);
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
     *     description="Toggle visibility for admin home tab",
     *     views = {"desktop_home"}
     * )
     */
    public function putAdminHomeTabVisibilityToggleAction(HomeTabConfig $htc)
    {
        $this->checkHomeTabConfig($htc, 'admin_desktop');
        $htc->setVisible(!$htc->isVisible());
        $this->homeTabManager->insertHomeTabConfig($htc);
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
            'color' => $color,
            'details' => $details
        );
        $event = new LogHomeTabAdminUserEditEvent($htc);
        $this->eventDispatcher->dispatch('log', $event);

        return new JsonResponse($htcDatas, 200);
    }

    /**
     * @View(serializerGroups={"api_home_tab"})
     * @ApiDoc(
     *     description="Returns the home tab creation form",
     *     views = {"desktop_home"}
     * )
     */
    public function getHomeTabCreationFormAction()
    {
        $this->checkHomeLocked();
        $formType = new HomeTabType();
        $formType->enableApi();
        $form = $this->createForm($formType);

        return $this->apiManager->handleFormView(
            'ClarolineCoreBundle:API:Desktop\homeTabCreateForm.html.twig',
            $form
        );
    }

    /**
     * @View()
     * @ApiDoc(
     *     description="Creates a home tab",
     *     views = {"desktop_home"},
     *     input="Claroline\CoreBundle\Form\HomeTabType"
     * )
     */
    public function postHomeTabCreationAction()
    {
        $this->checkHomeLocked();
        $user = $this->tokenStorage->getToken()->getUser();
        $formType = new HomeTabType();
        $formType->enableApi();
        $form = $this->createForm($formType);
        $form->submit($this->request);

        if ($form->isValid()) {
            $formDatas = $form->getData();
            $color = $form->get('color')->getData();

            $homeTab = new HomeTab();
            $homeTab->setName($formDatas['name']);
            $homeTab->setType('desktop');
            $homeTab->setUser($user);

            $homeTabConfig = new HomeTabConfig();
            $homeTabConfig->setHomeTab($homeTab);
            $homeTabConfig->setType('desktop');
            $homeTabConfig->setUser($user);
            $homeTabConfig->setLocked(false);
            $homeTabConfig->setVisible(true);
            $homeTabConfig->setDetails(array('color' => $color));

            $lastOrder = $this->homeTabManager->getOrderOfLastDesktopHomeTabConfigByUser($user);

            if (is_null($lastOrder['order_max'])) {
                $homeTabConfig->setTabOrder(1);
            } else {
                $homeTabConfig->setTabOrder($lastOrder['order_max'] + 1);
            }
            $this->homeTabManager->persistHomeTabConfigs($homeTab, $homeTabConfig);
            $event = new LogHomeTabUserCreateEvent($homeTabConfig);
            $this->eventDispatcher->dispatch('log', $event);

            $homeTabDatas = array(
                'configId' => $homeTabConfig->getId(),
                'locked' => $homeTabConfig->isLocked(),
                'tabOrder' => $homeTabConfig->getTabOrder(),
                'type' => $homeTabConfig->getType(),
                'visible' => $homeTabConfig->isVisible(),
                'tabId' => $homeTab->getId(),
                'tabName' => $homeTab->getName(),
                'tabType' => $homeTab->getType(),
                'tabIcon' => $homeTab->getIcon(),
                'color' => $color
            );

            return new JsonResponse($homeTabDatas, 200);
        } else {
            $options = array(
                'http_code' => 400,
                'extra_parameters' => null,
                'serializer_group' => 'api_home_tab'
            );

            return $this->apiManager->handleFormView(
                'ClarolineCoreBundle:API:Desktop\homeTabCreateForm.html.twig',
                $form,
                $options
            );
        }
    }

    /**
     * @View(serializerGroups={"api_home_tab"})
     * @ApiDoc(
     *     description="Returns the home tab edition form",
     *     views = {"desktop_home"}
     * )
     */
    public function getHomeTabEditionFormAction(HomeTab $homeTab)
    {
        $this->checkHomeLocked();
        $user = $this->tokenStorage->getToken()->getUser();
        $this->checkHomeTabEdition($homeTab, $user);

        $homeTabConfig = $this->homeTabManager->getHomeTabConfigByHomeTabAndUser($homeTab, $user);
        $details = !is_null($homeTabConfig) ? $homeTabConfig->getDetails() : null;
        $color = isset($details['color']) ? $details['color'] : null;

        $formType = new HomeTabType(null, false, $color);
        $formType->enableApi();
        $form = $this->createForm($formType, $homeTab);

        return $this->apiManager->handleFormView(
            'ClarolineCoreBundle:API:Desktop\homeTabEditForm.html.twig',
            $form
        );
    }

    /**
     * @View()
     * @ApiDoc(
     *     description="Edits a home tab",
     *     views = {"desktop_home"},
     *     input="Claroline\CoreBundle\Form\HomeTabType"
     * )
     */
    public function putHomeTabEditionAction(HomeTab $homeTab)
    {
        $this->checkHomeLocked();
        $user = $this->tokenStorage->getToken()->getUser();
        $this->checkHomeTabEdition($homeTab, $user);

        $formType = new HomeTabType();
        $formType->enableApi();
        $form = $this->createForm($formType);
        $form->submit($this->request);

        if ($form->isValid()) {
            $homeTabConfig = $this->homeTabManager->getHomeTabConfigByHomeTabAndUser($homeTab, $user);

            if (is_null($homeTabConfig)) {
                $homeTabConfig = new HomeTabConfig();
                $homeTabConfig->setHomeTab($homeTab);
                $homeTabConfig->setType('desktop');
                $homeTabConfig->setUser($user);
                $homeTabConfig->setLocked(false);
                $homeTabConfig->setVisible(true);
                $lastOrder = $this->homeTabManager->getOrderOfLastDesktopHomeTabConfigByUser($user);

                if (is_null($lastOrder['order_max'])) {
                    $homeTabConfig->setTabOrder(1);
                } else {
                    $homeTabConfig->setTabOrder($lastOrder['order_max'] + 1);
                }
            }
            $formDatas = $form->getData();
            $homeTab->setName($formDatas['name']);
            $color = $form->get('color')->getData();
            $details = $homeTabConfig->getDetails();

            if (is_null($details)) {
                $details = array();
            }
            $details['color'] = $color;
            $homeTabConfig->setDetails($details);
            $this->homeTabManager->persistHomeTabConfigs($homeTab, $homeTabConfig);
            $event = new LogHomeTabUserEditEvent($homeTabConfig);
            $this->eventDispatcher->dispatch('log', $event);

            $homeTabDatas = array(
                'configId' => $homeTabConfig->getId(),
                'locked' => $homeTabConfig->isLocked(),
                'tabOrder' => $homeTabConfig->getTabOrder(),
                'type' => $homeTabConfig->getType(),
                'visible' => $homeTabConfig->isVisible(),
                'tabId' => $homeTab->getId(),
                'tabName' => $homeTab->getName(),
                'tabType' => $homeTab->getType(),
                'tabIcon' => $homeTab->getIcon(),
                'color' => $color
            );

            return new JsonResponse($homeTabDatas, 200);
        } else {
            $options = array(
                'http_code' => 400,
                'extra_parameters' => null,
                'serializer_group' => 'api_home_tab'
            );

            return $this->apiManager->handleFormView(
                'ClarolineCoreBundle:API:Desktop\homeTabEditForm.html.twig',
                $form,
                $options
            );
        }
    }
    
    /**
     * @View(serializerGroups={"api_home_tab"})
     * @ApiDoc(
     *     description="Delete user home tab",
     *     views = {"desktop_home"}
     * )
     */
    public function deleteUserHomeTabAction(HomeTabConfig $htc)
    {
        $this->checkHomeTabConfig($htc, 'desktop');
        $user = $htc->getUser();
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
            'color' => $color,
            'details' => $details
        );
        $this->homeTabManager->deleteHomeTabConfig($htc);
        $this->homeTabManager->deleteHomeTab($tab);
        $event = new LogHomeTabUserDeleteEvent($user, $htcDatas);
        $this->eventDispatcher->dispatch('log', $event);

        return new JsonResponse($htcDatas, 200);
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
        $this->checkHomeTabConfig($htc, 'workspace_user');
        $user = $htc->getUser();
        $workspace = $htc->getWorkspace();
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
            'color' => $color,
            'details' => $details
        );
        $this->homeTabManager->deleteHomeTabConfig($htc);
        $event = new LogHomeTabWorkspaceUnpinEvent($user, $workspace, $htcDatas);
        $this->eventDispatcher->dispatch('log', $event);

        return new JsonResponse($htcDatas, 200);
    }

    private function checkHomeLocked()
    {
        $user = $this->tokenStorage->getToken()->getUser();

        if ($user === '.anon' || $this->roleManager->isHomeLocked($user)) {

            throw new AccessDeniedException();
        }
    }

    private function checkHomeTabConfig(HomeTabConfig $htc, $homeTabType)
    {
        $authenticatedUser = $this->tokenStorage->getToken()->getUser();
        $user = $htc->getUser();
        $type = $htc->getType();

        if ($type !== $homeTabType || $authenticatedUser !== $user) {

            throw new AccessDeniedException();
        }
    }

    private function checkHomeTabEdition(HomeTab $homeTab, User $user)
    {
        $homeTabUser = $homeTab->getUser();
        $homeTabType = $homeTab->getType();

        if ($homeTabType !== 'desktop' || $user !== $homeTabUser) {

            throw new AccessDeniedException();
        }
    }
}
