<?php

namespace Claroline\CoreBundle\Controller;

use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Entity\Widget\WidgetHomeTabConfig;
use Claroline\CoreBundle\Event\StrictDispatcher;
use Claroline\CoreBundle\Manager\HomeTabManager;
use Claroline\CoreBundle\Manager\ToolManager;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use JMS\SecurityExtraBundle\Annotation as SEC;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * @DI\Tag("security.secure_service")
 * @SEC\PreAuthorize("hasRole('ROLE_USER')")
 */
class DesktopController extends Controller
{
    private $em;
    private $eventDispatcher;
    private $homeTabManager;
    private $router;
    private $toolManager;


    /**
     * @DI\InjectParams({
     *     "em"                 = @DI\Inject("doctrine.orm.entity_manager"),
     *     "eventDispatcher"    = @DI\Inject("claroline.event.event_dispatcher"),
     *     "homeTabManager"     = @DI\Inject("claroline.manager.home_tab_manager"),
     *     "router"             = @DI\Inject("router"),
     *     "toolManager"        = @DI\Inject("claroline.manager.tool_manager"),
     * })
     */
    public function __construct(
        EntityManager $em,
        StrictDispatcher $eventDispatcher,
        HomeTabManager $homeTabManager,
        UrlGeneratorInterface $router,
        ToolManager $toolManager
    )
    {
        $this->em = $em;
        $this->eventDispatcher = $eventDispatcher;
        $this->homeTabManager = $homeTabManager;
        $this->router = $router;
        $this->toolManager = $toolManager;
    }

    /**
     * @EXT\Route(
     *     "/home_tab/{homeTabId}/widgets/{withConfig}",
     *     name="claro_desktop_widgets"
     * )
     * @EXT\ParamConverter("user", options={"authenticatedUser" = true})
     * @EXT\Template("ClarolineCoreBundle:Widget:widgets.html.twig")
     *
     * Displays registered widgets.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function widgetsAction($homeTabId, $withConfig, User $user)
    {
        $widgets = array();
        $configs = array();
        $lastWidgetOrder = 1;

        $homeTab = $this->homeTabManager->getHomeTabById($homeTabId);

        if (is_null($homeTab)) {
            $isVisibleHomeTab = false;
        } else {
            $isVisibleHomeTab = $this->homeTabManager
                ->checkHomeTabVisibilityByUser($homeTab, $user, $withConfig);
            $isLockedHomeTab = $this->homeTabManager->checkHomeTabLock($homeTab);
        }

        if ($isVisibleHomeTab) {

            if ($homeTab->getType() === 'admin_desktop') {
                $adminConfigs = $this->homeTabManager->getAdminWidgetConfigs($homeTab);

                if (!$isLockedHomeTab) {
                    $userWidgetsConfigs = $this->homeTabManager
                        ->getWidgetConfigsByUser($homeTab, $user);
                } else {
                    $userWidgetsConfigs = array();
                }

                if (count($userWidgetsConfigs) > 0) {
                    $lastWidgetOrder = count($userWidgetsConfigs);
                }

                foreach ($adminConfigs as $adminConfig) {

                    if ($adminConfig->isLocked()) {
                        if ($adminConfig->isVisible()) {
                            $configs[] = $adminConfig;
                        }
                    }
                    else {
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
                        }
                        else {
                            $configs[] = $existingWidgetConfig[0];
                        }
                    }
                }

                foreach ($userWidgetsConfigs as $userWidgetsConfig) {
                    $configs[] = $userWidgetsConfig;
                }
            } else {
                $configs = $this->homeTabManager->getWidgetConfigsByUser($homeTab, $user);

                if (count($configs) > 0) {
                    $lastWidgetOrder = count($configs);
                }
            }

            foreach ($configs as $config) {
                $event = $this->eventDispatcher->dispatch(
                    "widget_{$config->getWidgetInstance()->getWidget()->getName()}",
                    'DisplayWidget',
                    array($config->getWidgetInstance())
                );

                $widget['config']= $config;
                $widget['content'] = $event->getContent();
                $widget['configurable'] = ($config->isLocked() !== true && $config->getWidgetInstance()->getWidget()->isConfigurable());
                $widgets[] = $widget;
            }
        }

        return array(
            'widgetsDatas' => $widgets,
            'isDesktop' => true,
            'withConfig' => $withConfig,
            'isVisibleHomeTab' => $isVisibleHomeTab,
            'isLockedHomeTab' => $isLockedHomeTab,
            'lastWidgetOrder' => $lastWidgetOrder
        );
    }

    /**
     * @EXT\Template()
     * @EXT\ParamConverter("user", options={"authenticatedUser" = true})
     *
     * Renders the left tool bar. Not routed.
     *
     * @return Response
     */
    public function renderToolListAction(User $user)
    {
        return array('tools' => $this->toolManager->getDisplayedDesktopOrderedTools($user));
    }

    /**
     * @EXT\Route(
     *     "tool/open/{toolName}",
     *     name="claro_desktop_open_tool",
     *     options={"expose"=true}
     * )
     *
     * Opens a tool.
     *
     * @param string $toolName
     *
     * @throws \Exception
     * @return Response
     */
    public function openToolAction($toolName)
    {
        $event = $this->eventDispatcher->dispatch(
            'open_tool_desktop_'.$toolName,
            'DisplayTool'
        );

        if ($toolName === 'resource_manager') {
            $this->get('session')->set('isDesktop', true);
        }

        return new Response($event->getContent());
    }

    /**
     * @EXT\Route(
     *     "/open",
     *     name="claro_desktop_open"
     * )
     * @EXT\ParamConverter("user", options={"authenticatedUser" = true})
     *
     * Opens the desktop.
     *
     * @return Response
     */
    public function openAction(User $user)
    {
        $openedTool = $this->toolManager->getDisplayedDesktopOrderedTools($user);

        $route = $this->router->generate(
            'claro_desktop_open_tool',
            array('toolName' => $openedTool[0]->getName())
        );

        return new RedirectResponse($route);
    }
}
