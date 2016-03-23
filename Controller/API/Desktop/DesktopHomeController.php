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
use Claroline\CoreBundle\Entity\Widget\WidgetDisplayConfig;
use Claroline\CoreBundle\Entity\Widget\WidgetHomeTabConfig;
use Claroline\CoreBundle\Entity\Widget\WidgetInstance;
use Claroline\CoreBundle\Event\Log\LogHomeTabAdminUserEditEvent;
use Claroline\CoreBundle\Event\Log\LogHomeTabUserCreateEvent;
use Claroline\CoreBundle\Event\Log\LogHomeTabUserDeleteEvent;
use Claroline\CoreBundle\Event\Log\LogHomeTabUserEditEvent;
use Claroline\CoreBundle\Event\Log\LogHomeTabWorkspaceUnpinEvent;
use Claroline\CoreBundle\Event\Log\LogWidgetAdminHideEvent;
use Claroline\CoreBundle\Event\Log\LogWidgetUserCreateEvent;
use Claroline\CoreBundle\Event\Log\LogWidgetUserDeleteEvent;
use Claroline\CoreBundle\Event\Log\LogWidgetUserEditEvent;
use Claroline\CoreBundle\Event\StrictDispatcher;
use Claroline\CoreBundle\Form\HomeTabType;
use Claroline\CoreBundle\Form\WidgetInstanceConfigType;
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
     *     description="Returns desktop options",
     *     views = {"desktop_home"}
     * )
     */
    public function getDesktopOptionsAction()
    {
        $token = $this->tokenStorage->getToken();
        $user = $token->getUser();

        if ($user === '.anon') {

            throw new AccessDeniedException();
        } else {
            $options = $this->userManager->getUserOptions($user);
            $desktopOptions = array();
            $desktopOptions['editionMode'] = $options->getDesktopMode() === 1;
            $desktopOptions['isHomeLocked'] = $this->roleManager->isHomeLocked($user);

            return $desktopOptions;
        }
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
            $userHomeTabConfigs = array();
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
        $isHomeLocked = $this->roleManager->isHomeLocked($user);
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

                if ($isLockedHomeTab || $isHomeLocked) {

                    foreach ($adminConfigs as $adminConfig) {

                        if ($adminConfig->isVisible()) {
                            $configs[] = $adminConfig;
                        }
                    }
                } else {
                    $userWidgetsConfigs = $this->homeTabManager
                        ->getWidgetConfigsByUser($homeTab, $user);

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
                            if (is_null($existingWidgetConfig) && $adminConfig->isVisible()) {
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
                            } else if ($existingWidgetConfig->isVisible()){
                                $configs[] = $existingWidgetConfig;
                            }
                        }
                    }

                    foreach ($userWidgetsConfigs as $userWidgetsConfig) {
                        $configs[] = $userWidgetsConfig;
                    }
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

            if ($isWorkspace) {
                $wdcs = $this->widgetManager->generateWidgetDisplayConfigsForWorkspace(
                    $workspace,
                    $configs
                );
            } else if ($isLockedHomeTab || $isHomeLocked) {
                $wdcs = $this->widgetManager->getAdminWidgetDisplayConfigsByWHTCs($configs);
            } else {
                $wdcs = $this->widgetManager->generateWidgetDisplayConfigsForUser(
                    $user,
                    $configs
                );
            }

            foreach ($wdcs as $wdc) {

                if ($wdc->getRow() === -1 || $wdc->getColumn() === -1) {
                    $widgetsDatas['initWidgetsPosition'] = true;
                    break;
                }
            }

            foreach ($configs as $config) {
                $widgetDatas = array();
                $widgetInstance = $config->getWidgetInstance();
                $widget = $widgetInstance->getWidget();
                $widgetInstanceId = $widgetInstance->getId();
                $widgetDatas['widgetId'] = $widget->getId();
                $widgetDatas['widgetName'] = $widget->getName();
                $widgetDatas['configId'] = $config->getId();

                // TODO
                // Retrieve widget content

//                $eventName = 'widget_' . $config->getWidgetInstance()->getWidget()->getName();
//                $event = $this->eventDispatcher->dispatch(
//                    $eventName,
//                    new \Claroline\CoreBundle\Event\DisplayWidgetEvent($widgetInstance)
//                );

//                $event = $this->eventStrictDispatcher->dispatch(
//                    "widget_{$config->getWidgetInstance()->getWidget()->getName()}",
//                    'DisplayWidget',
//                    array($config->getWidgetInstance())
//                );

//                $widgetDatas['content'] = $event->getContent();
                $widgetDatas['configurable'] = $config->isLocked() !== true
                    && $config->getWidgetInstance()->getWidget()->isConfigurable();
                $widgetDatas['locked'] = $config->isLocked();
                $widgetDatas['type'] = $config->getType();
                $widgetDatas['instanceId'] = $widgetInstanceId;
                $widgetDatas['instanceName'] = $widgetInstance->getName();
                $widgetDatas['instanceIcon'] = $widgetInstance->getIcon();
                $widgetDatas['displayId'] = $wdcs[$widgetInstanceId]->getId();
                $row = $wdcs[$widgetInstanceId]->getRow();
                $column = $wdcs[$widgetInstanceId]->getColumn();
                $widgetDatas['row'] = $row >= 0 ? $row : null;
                $widgetDatas['col'] = $column >= 0 ? $column : null;
                $widgetDatas['sizeY'] = $wdcs[$widgetInstanceId]->getHeight();
                $widgetDatas['sizeX'] = $wdcs[$widgetInstanceId]->getWidth();
                $widgetDatas['color'] = $wdcs[$widgetInstanceId]->getColor();
                $details = $wdcs[$widgetInstanceId]->getDetails();
                $widgetDatas['textTitleColor'] = isset($details['textTitleColor']) ?
                    $details['textTitleColor'] :
                    null;
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
     *     description="Creates a desktop home tab",
     *     views = {"desktop_home"},
     *     input="Claroline\CoreBundle\Form\HomeTabType"
     * )
     */
    public function postDesktopHomeTabCreationAction()
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
        $event = new LogHomeTabUserDeleteEvent($htcDatas);
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

    /**
     * @View(serializerGroups={"api_home_tab"})
     * @ApiDoc(
     *     description="Returns the widget instance creation form",
     *     views = {"desktop_home"}
     * )
     */
    public function getWidgetInstanceCreationFormAction(HomeTabConfig $htc)
    {
        $this->checkWidgetCreation($htc);
        $user = $this->tokenStorage->getToken()->getUser();
        $formType = new WidgetInstanceConfigType(true, true, $user->getEntityRoles());
        $formType->enableApi();
        $form = $this->createForm($formType);

        return $this->apiManager->handleFormView(
            'ClarolineCoreBundle:API:Widget\widgetInstanceCreateForm.html.twig',
            $form
        );
    }

    /**
     * @View(serializerGroups={"api_home_tab"})
     * @ApiDoc(
     *     description="Returns the widget instance edition form",
     *     views = {"desktop_home"}
     * )
     */
    public function getWidgetInstanceEditionFormAction(WidgetDisplayConfig $wdc)
    {
        $this->checkWidgetDisplayConfigEdition($wdc);
        $widgetInstance = $wdc->getWidgetInstance();
        $this->checkWidgetInstanceEdition($widgetInstance);
        $color = $wdc->getColor();
        $details = $wdc->getDetails();
        $textTitleColor = isset($details['textTitleColor']) ? $details['textTitleColor'] : null;
        $formType = new WidgetInstanceConfigType(true, false, array(), $color, $textTitleColor, 'wfmc', false);
        $formType->enableApi();
        $form = $this->createForm($formType, $widgetInstance);

//        $widget = $widgetInstance->getWidget();
//
//        if ($widget->isConfigurable()) {
//            $event = $this->get('claroline.event.event_dispatcher')->dispatch(
//                "widget_{$widgetInstance->getWidget()->getName()}_configuration",
//                'ConfigureWidget',
//                array($widgetInstance)
//            );
//            $content = $event->getContent();
//        } else {
//            $content = array();
//        }

        return $this->apiManager->handleFormView(
            'ClarolineCoreBundle:API:Widget\widgetInstanceEditForm.html.twig',
            $form
        );
    }

    /**
     * @View(serializerGroups={"api_home_tab"})
     * @ApiDoc(
     *     description="Creates a new widget instance",
     *     views = {"desktop_home"}
     * )
     */
    public function postDesktopWidgetInstanceCreationAction(HomeTabConfig $htc)
    {
        $this->checkWidgetCreation($htc);
        $user = $this->tokenStorage->getToken()->getUser();
        $formType = new WidgetInstanceConfigType(true, true, $user->getEntityRoles());
        $formType->enableApi();
        $form = $this->createForm($formType);
        $form->submit($this->request);

        if ($form->isValid()) {
            $homeTab = $htc->getHomeTab();
            $formDatas = $form->getData();
            $widget = $formDatas['widget'];
            $color = $form->get('color')->getData();
            $textTitleColor = $form->get('textTitleColor')->getData();

            $widgetInstance = new WidgetInstance();
            $widgetHomeTabConfig = new WidgetHomeTabConfig();
            $widgetDisplayConfig = new WidgetDisplayConfig();
            $widgetInstance->setName($formDatas['name']);
            $widgetInstance->setUser($user);
            $widgetInstance->setWidget($widget);
            $widgetInstance->setIsAdmin(false);
            $widgetInstance->setIsDesktop(true);
            $widgetHomeTabConfig->setHomeTab($homeTab);
            $widgetHomeTabConfig->setWidgetInstance($widgetInstance);
            $widgetHomeTabConfig->setUser($user);
            $widgetHomeTabConfig->setVisible(true);
            $widgetHomeTabConfig->setLocked(false);
            $widgetHomeTabConfig->setWidgetOrder(1);
            $widgetHomeTabConfig->setType('desktop');
            $widgetDisplayConfig->setWidgetInstance($widgetInstance);
            $widgetDisplayConfig->setUser($user);
            $widgetDisplayConfig->setWidth($widget->getDefaultWidth());
            $widgetDisplayConfig->setHeight($widget->getDefaultHeight());
            $widgetDisplayConfig->setColor($color);
            $widgetDisplayConfig->setDetails(array('textTitleColor' => $textTitleColor));

            $this->widgetManager->persistWidgetConfigs(
                $widgetInstance,
                $widgetHomeTabConfig,
                $widgetDisplayConfig
            );
            $event = new LogWidgetUserCreateEvent($homeTab, $widgetHomeTabConfig, $widgetDisplayConfig);
            $this->eventDispatcher->dispatch('log', $event);

            $widgetDatas = array(
                'widgetId' => $widget->getId(),
                'widgetName' => $widget->getName(),
                'configId' => $widgetHomeTabConfig->getId(),
                'configurable' => $widgetHomeTabConfig->isLocked() !== true && $widget->isConfigurable(),
                'locked' => $widgetHomeTabConfig->isLocked(),
                'type' => $widgetHomeTabConfig->getType(),
                'instanceId' => $widgetInstance->getId(),
                'instanceName' => $widgetInstance->getName(),
                'instanceIcon' => $widgetInstance->getIcon(),
                'displayId' => $widgetDisplayConfig->getId(),
                'row' => null,
                'col' => null,
                'sizeY' => $widgetDisplayConfig->getHeight(),
                'sizeX' => $widgetDisplayConfig->getWidth(),
                'color' => $color,
                'textTitleColor' => $textTitleColor
            );

            return new JsonResponse($widgetDatas, 200);
        } else {
            $options = array(
                'http_code' => 400,
                'extra_parameters' => null,
                'serializer_group' => 'api_widget'
            );

            return $this->apiManager->handleFormView(
                'ClarolineCoreBundle:API:Widget\widgetInstanceCreateForm.html.twig',
                $form,
                $options
            );
        }
    }

    /**
     * @View(serializerGroups={"api_home_tab"})
     * @ApiDoc(
     *     description="Edit widget instance config",
     *     views = {"desktop_home"}
     * )
     */
    public function putWidgetInstanceEditionAction(WidgetDisplayConfig $wdc)
    {
        $this->checkWidgetDisplayConfigEdition($wdc);
        $widgetInstance = $wdc->getWidgetInstance();
        $this->checkWidgetInstanceEdition($widgetInstance);
        $color = $wdc->getColor();
        $details = $wdc->getDetails();
        $textTitleColor = isset($details['textTitleColor']) ? $details['textTitleColor'] : null;
        $formType = new WidgetInstanceConfigType(true, false, array(), $color, $textTitleColor, 'wfmc', false);
        $formType->enableApi();
        $form = $this->createForm($formType, $widgetInstance);
        $form->submit($this->request);

        if ($form->isValid()) {
            $instance = $form->getData();
            $name = $instance->getName();
            $color = $form->get('color')->getData();
            $textTitleColor = $form->get('textTitleColor')->getData();
            $widgetInstance->setName($name);
            $wdc->setColor($color);
            $details = $wdc->getDetails();

            if (is_null($details)) {
                $details = array();
            }
            $details['textTitleColor'] = $textTitleColor;
            $wdc->setDetails($details);

            $this->widgetManager->persistWidgetConfigs($widgetInstance, null, $wdc);
            $event = new LogWidgetUserEditEvent($widgetInstance, null, $wdc);
            $this->eventDispatcher->dispatch('log', $event);
            $widget = $widgetInstance->getWidget();

            $widgetDatas = array(
                'widgetId' => $widget->getId(),
                'widgetName' => $widget->getName(),
                'instanceId' => $widgetInstance->getId(),
                'instanceName' => $widgetInstance->getName(),
                'instanceIcon' => $widgetInstance->getIcon(),
                'displayId' => $wdc->getId(),
                'row' => null,
                'col' => null,
                'sizeY' => $wdc->getHeight(),
                'sizeX' => $wdc->getWidth(),
                'color' => $color,
                'textTitleColor' => $textTitleColor
            );

            return new JsonResponse($widgetDatas, 200);
        } else {
            $options = array(
                'http_code' => 400,
                'extra_parameters' => null,
                'serializer_group' => 'api_widget'
            );

            return $this->apiManager->handleFormView(
                'ClarolineCoreBundle:API:Widget\widgetInstanceEditForm.html.twig',
                $form,
                $options
            );
        }
    }

    /**
     * @View(serializerGroups={"api_home_tab"})
     * @ApiDoc(
     *     description="Change visibility of a widget",
     *     views = {"desktop_home"}
     * )
     */
    public function putDesktopWidgetHomeTabConfigVisibilityChangeAction(
        WidgetHomeTabConfig $widgetHomeTabConfig
    )
    {
        $this->checkWidgetHomeTabConfigEdition($widgetHomeTabConfig);
        $this->homeTabManager->changeVisibilityWidgetHomeTabConfig($widgetHomeTabConfig, false);
        $homeTab = $widgetHomeTabConfig->getHomeTab();
        $event = new LogWidgetAdminHideEvent($homeTab, $widgetHomeTabConfig);
        $this->eventDispatcher->dispatch('log', $event);

        $widgetInstance = $widgetHomeTabConfig->getWidgetInstance();
        $widget = $widgetInstance->getWidget();
        $datas = array(
            'widgetId' => $widget->getId(),
            'widgetName' => $widget->getName(),
            'widgetIsConfigurable' => $widget->isConfigurable(),
            'widgetIsExportable' => $widget->isExportable(),
            'widgetIsDisplayableInWorkspace' => $widget->isDisplayableInWorkspace(),
            'widgetIsDisplayableInDesktop' => $widget->isDisplayableInDesktop(),
            'id' => $widgetInstance->getId(),
            'name' => $widgetInstance->getName(),
            'icon' => $widgetInstance->getIcon(),
            'isAdmin' => $widgetInstance->isAdmin(),
            'isDesktop' => $widgetInstance->isDesktop(),
            'widgetHomeTabConfigId' => $widgetHomeTabConfig->getId(),
            'order' => $widgetHomeTabConfig->getWidgetOrder(),
            'type' => $widgetHomeTabConfig->getType(),
            'visible' => $widgetHomeTabConfig->isVisible(),
            'locked' => $widgetHomeTabConfig->isLocked()
        );

        return new JsonResponse($datas, 200);
    }

    /**
     * @View(serializerGroups={"api_home_tab"})
     * @ApiDoc(
     *     description="Delete a widget",
     *     views = {"desktop_home"}
     * )
     */
    public function deleteDesktopWidgetHomeTabConfigAction(WidgetHomeTabConfig $widgetHomeTabConfig)
    {
        $this->checkWidgetHomeTabConfigEdition($widgetHomeTabConfig);
        $homeTab = $widgetHomeTabConfig->getHomeTab();
        $widgetInstance = $widgetHomeTabConfig->getWidgetInstance();
        $widget = $widgetInstance->getWidget();
        $datas = array(
            'tabId' => $homeTab->getId(),
            'tabName' => $homeTab->getName(),
            'tabType' => $homeTab->getType(),
            'tabIcon' => $homeTab->getIcon(),
            'widgetId' => $widget->getId(),
            'widgetName' => $widget->getName(),
            'widgetIsConfigurable' => $widget->isConfigurable(),
            'widgetIsExportable' => $widget->isExportable(),
            'widgetIsDisplayableInWorkspace' => $widget->isDisplayableInWorkspace(),
            'widgetIsDisplayableInDesktop' => $widget->isDisplayableInDesktop(),
            'id' => $widgetInstance->getId(),
            'name' => $widgetInstance->getName(),
            'icon' => $widgetInstance->getIcon(),
            'isAdmin' => $widgetInstance->isAdmin(),
            'isDesktop' => $widgetInstance->isDesktop(),
            'widgetHomeTabConfigId' => $widgetHomeTabConfig->getId(),
            'order' => $widgetHomeTabConfig->getWidgetOrder(),
            'type' => $widgetHomeTabConfig->getType(),
            'visible' => $widgetHomeTabConfig->isVisible(),
            'locked' => $widgetHomeTabConfig->isLocked()
        );
        $this->homeTabManager->deleteWidgetHomeTabConfig($widgetHomeTabConfig);

        if ($this->hasUserAccessToWidgetInstance($widgetInstance)) {
            $this->widgetManager->removeInstance($widgetInstance);
        }
        $event = new LogWidgetUserDeleteEvent($datas);
        $this->eventDispatcher->dispatch('log', $event);

        return new JsonResponse($datas, 200);
    }

    /**
     * @View(serializerGroups={"api_home_tab"})
     * @ApiDoc(
     *     description="Update widgets display",
     *     views = {"desktop_home"}
     * )
     */
    public function putDesktopWidgetDisplayUpdateAction($datas)
    {
        $jsonDatas = json_decode($datas, true);
        $displayConfigs = array();

        foreach($jsonDatas as $data) {
            $displayConfig = $this->widgetManager->getWidgetDisplayConfigById($data['id']);

            if (!is_null($displayConfig)) {
                $this->checkWidgetDisplayConfigEdition($displayConfig);
                $displayConfig->setRow($data['row']);
                $displayConfig->setColumn($data['col']);
                $displayConfig->setWidth($data['sizeX']);
                $displayConfig->setHeight($data['sizeY']);
                $displayConfigs[] = $displayConfig;
            }
        }
        $this->widgetManager->persistWidgetDisplayConfigs($displayConfigs);

        return new JsonResponse($jsonDatas, 200);
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

    private function checkWidgetHomeTabConfigEdition(WidgetHomeTabConfig $whtc)
    {
        $authenticatedUser = $this->tokenStorage->getToken()->getUser();
        $user = $whtc->getUser();

        if ($authenticatedUser !== $user) {

            throw new AccessDeniedException();
        }
    }

    private function checkWidgetDisplayConfigEdition(WidgetDisplayConfig $wdc)
    {
        $authenticatedUser = $this->tokenStorage->getToken()->getUser();
        $user = $wdc->getUser();

        if ($authenticatedUser !== $user) {

            throw new AccessDeniedException();
        }
    }

    private function checkWidgetInstanceEdition(WidgetInstance $widgetInstance)
    {
        $authenticatedUser = $this->tokenStorage->getToken()->getUser();
        $user = $widgetInstance->getUser();

        if ($authenticatedUser !== $user) {

            throw new AccessDeniedException();
        }
    }

    private function checkWidgetCreation(HomeTabConfig $htc)
    {
        $user = $this->tokenStorage->getToken()->getUser();
        $homeTab = $htc->getHomeTab();
        $homeTabUser = $homeTab->getUser();
        $type = $homeTab->getType();
        $locked = $htc->isLocked();
        $visible = $htc->isVisible();
        $canCreate = $visible &&
            !$locked &&
            (($type === 'desktop' && $homeTabUser === $user) || ($type === 'admin_desktop' && $visible && !$locked));

        if ($user === '.anon' || $this->roleManager->isHomeLocked($user) || !$canCreate) {

            throw new AccessDeniedException();
        }
    }

    private function hasUserAccessToWidgetInstance(WidgetInstance $widgetInstance)
    {
        $authenticatedUser = $this->tokenStorage->getToken()->getUser();
        $user = $widgetInstance->getUser();

        return $authenticatedUser === $user;
    }
}
