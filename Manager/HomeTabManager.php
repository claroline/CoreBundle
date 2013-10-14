<?php

namespace Claroline\CoreBundle\Manager;

use Claroline\CoreBundle\Entity\Home\HomeTab;
use Claroline\CoreBundle\Entity\Home\HomeTabConfig;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Entity\Widget\WidgetInstance;
use Claroline\CoreBundle\Entity\Widget\WidgetHomeTabConfig;
use Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace;
use Claroline\CoreBundle\Persistence\ObjectManager;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * @DI\Service("claroline.manager.home_tab_manager")
 */
class HomeTabManager
{
    /** @var HomeTabRepository */
    private $homeTabRepo;
    /** @var HomeTabConfigRepository */
    private $homeTabConfigRepo;
    /** @var WidgetHomeTabConfigRepository */
    private $widgetHomeTabConfigRepo;
    private $om;

    /**
     * Constructor.
     *
     * @DI\InjectParams({
     *     "om" = @DI\Inject("claroline.persistence.object_manager")
     * })
     */
    public function __construct(ObjectManager $om)
    {
        $this->homeTabRepo = $om->getRepository(
            'ClarolineCoreBundle:Home\HomeTab'
        );
        $this->homeTabConfigRepo = $om->getRepository(
            'ClarolineCoreBundle:Home\HomeTabConfig'
        );
        $this->widgetHomeTabConfigRepo = $om->getRepository(
            'ClarolineCoreBundle:Widget\WidgetHomeTabConfig'
        );
        $this->om = $om;
    }

    public function insertHomeTab(HomeTab $homeTab)
    {
        $this->om->persist($homeTab);
        $this->om->flush();
    }

    public function deleteHomeTab(HomeTab $homeTab, $type, $tabOrder)
    {
        switch ($type) {
            case 'admin_desktop':
                $this->homeTabConfigRepo
                    ->updateAdminDesktopOrder($tabOrder);
                break;
            case 'admin_workspace':
                $this->homeTabConfigRepo
                    ->updateAdminWorkspaceOrder($tabOrder);
                break;
            case 'desktop':
                $this->homeTabConfigRepo
                    ->updateDesktopOrder($homeTab->getUser(), $tabOrder);
                break;
            case 'workspace':
                $this->homeTabConfigRepo
                    ->updateWorkspaceOrder($homeTab->getWorkspace(), $tabOrder);
                break;
        }
        $this->om->remove($homeTab);
        $this->om->flush();
    }

    public function insertHomeTabConfig(HomeTabConfig $homeTabConfig)
    {
        $this->om->persist($homeTabConfig);
        $this->om->flush();
    }

    public function updateVisibility(HomeTabConfig $homeTabConfig, $visible)
    {
        $homeTabConfig->setVisible($visible);
        $this->om->flush();
    }

    public function updateLock(HomeTabConfig $homeTabConfig, $locked)
    {
        $homeTabConfig->setLocked($locked);
        $this->om->flush();
    }

    public function createWorkspaceVersion(
        HomeTabConfig $homeTabConfig,
        AbstractWorkspace $workspace
    )
    {
        $newHomeTabConfig = new HomeTabConfig();
        $newHomeTabConfig->setHomeTab($homeTabConfig->getHomeTab());
        $newHomeTabConfig->setType($homeTabConfig->getType());
        $newHomeTabConfig->setWorkspace($workspace);
        $newHomeTabConfig->setVisible($homeTabConfig->isVisible());
        $newHomeTabConfig->setLocked($homeTabConfig->isLocked());
        $newHomeTabConfig->setTabOrder($homeTabConfig->getTabOrder());
        $this->om->persist($newHomeTabConfig);
        $this->om->flush();

        return $newHomeTabConfig;
    }

    public function createUserVersion(
        HomeTabConfig $homeTabConfig,
        User $user
    )
    {
        $newHomeTabConfig = new HomeTabConfig();
        $newHomeTabConfig->setHomeTab($homeTabConfig->getHomeTab());
        $newHomeTabConfig->setType($homeTabConfig->getType());
        $newHomeTabConfig->setUser($user);
        $newHomeTabConfig->setVisible($homeTabConfig->isVisible());
        $newHomeTabConfig->setLocked($homeTabConfig->isLocked());
        $newHomeTabConfig->setTabOrder($homeTabConfig->getTabOrder());
        $this->om->persist($newHomeTabConfig);
        $this->om->flush();

        return $newHomeTabConfig;
    }

    public function generateAdminHomeTabConfigsByUser(User $user)
    {
        $adminHTC = array();
        $adminHomeTabConfigs = $this->homeTabConfigRepo
            ->findAdminDesktopHomeTabConfigs();

        foreach ($adminHomeTabConfigs as $adminHomeTabConfig) {

            if ($adminHomeTabConfig->isLocked()) {
                if ($adminHomeTabConfig->isVisible()) {
                    $adminHTC[] = $adminHomeTabConfig;
                }
            }
            else {
                $existingCustomHTC = $this->homeTabConfigRepo->findOneBy(
                    array(
                        'homeTab' => $adminHomeTabConfig->getHomeTab(),
                        'user' => $user
                    )
                );

                if (is_null($existingCustomHTC)) {
                    $customHTC = $this->createUserVersion(
                        $adminHomeTabConfig,
                        $user
                    );
                    $adminHTC[] = $customHTC;
                }
                else {
                    $adminHTC[] = $existingCustomHTC;
                }
            }
        }

        return $adminHTC;
    }

    public function filterVisibleHomeTabConfigs(array $homeTabConfigs)
    {
        $visibleHomeTabConfigs = array();

        foreach ($homeTabConfigs as $homeTabConfig) {

            if ($homeTabConfig->isVisible()) {
                $visibleHomeTabConfigs[] = $homeTabConfig;
            }
        }

        return $visibleHomeTabConfigs;
    }

    public function checkHomeTabLock(HomeTab $homeTab)
    {
        $adminHomeTabConfig = $this->homeTabConfigRepo->findOneBy(
            array(
                'homeTab' => $homeTab,
                'type' => 'admin_desktop',
                'user' => null,
                'workspace' => null
            )
        );

        if (!is_null($adminHomeTabConfig)) {

            return $adminHomeTabConfig->isLocked();
        }

        return false;
    }

    public function checkHomeTabVisibilityForConfigByUser(
        HomeTab $homeTab,
        User $user
    )
    {
        $adminHomeTabConfig = $this->homeTabConfigRepo->findOneBy(
            array(
                'homeTab' => $homeTab,
                'type' => 'admin_desktop',
                'user' => null,
                'workspace' => null
            )
        );
        $userHomeTabConfig = $this->homeTabConfigRepo->findOneBy(
            array(
                'homeTab' => $homeTab,
                'user' => $user
            )
        );

        if (is_null($adminHomeTabConfig) && is_null($userHomeTabConfig)) {

            return false;
        }
        elseif (is_null($userHomeTabConfig)) {

            return $adminHomeTabConfig->isVisible();
        }
        elseif (is_null($adminHomeTabConfig)) {

            return true;
        }
        else {
            $visible = $adminHomeTabConfig->isLocked() ?
                $adminHomeTabConfig->isVisible() :
                true;

            return $visible;
        }
    }

    public function checkHomeTabVisibilityByUser(
        HomeTab $homeTab,
        User $user
    )
    {
        $adminHomeTabConfig = $this->homeTabConfigRepo->findOneBy(
            array(
                'homeTab' => $homeTab,
                'type' => 'admin_desktop',
                'user' => null,
                'workspace' => null
            )
        );
        $userHomeTabConfig = $this->homeTabConfigRepo->findOneBy(
            array(
                'homeTab' => $homeTab,
                'user' => $user
            )
        );

        if (is_null($adminHomeTabConfig) && is_null($userHomeTabConfig)) {

            return false;
        }
        elseif (is_null($userHomeTabConfig)) {

            return $adminHomeTabConfig->isVisible();
        }
        elseif (is_null($adminHomeTabConfig)) {

            return $userHomeTabConfig->isVisible();
        }
        else {
            $visible = $adminHomeTabConfig->isLocked() ?
                $adminHomeTabConfig->isVisible() :
                $userHomeTabConfig->isVisible();

            return $visible;
        }
    }

    public function checkHomeTabVisibilityByWorkspace(
        HomeTab $homeTab,
        AbstractWorkspace $workspace
    )
    {
        $homeTabConfig = $this->homeTabConfigRepo->findOneBy(
            array(
                'homeTab' => $homeTab,
                'workspace' => $workspace
            )
        );

        if (is_null($homeTabConfig)) {

            return false;
        }

        return $homeTabConfig->isVisible();
    }

    public function insertWidgetHomeTabConfig(
        WidgetHomeTabConfig $widgetHomeTabConfig
    )
    {
        $this->om->persist($widgetHomeTabConfig);
        $this->om->flush();
    }

    public function deleteWidgetHomeTabConfig(
        WidgetHomeTabConfig $widgetHomeTabConfig
    )
    {
        $widgetOrder = $widgetHomeTabConfig->getWidgetOrder();
        $homeTab = $widgetHomeTabConfig->getHomeTab();
        $user = $widgetHomeTabConfig->getUser();
        $workspace = $widgetHomeTabConfig->getWorkspace();

        if (is_null($user) && is_null($workspace)) {
            $this->widgetHomeTabConfigRepo->updateAdminWidgetHomeTabConfig(
                $homeTab,
                $widgetOrder
            );
        }
        elseif (is_null($workspace)) {
            $this->widgetHomeTabConfigRepo->updateWidgetHomeTabConfigByUser(
                $homeTab,
                $widgetOrder,
                $user
            );
        }
        else {
            $this->widgetHomeTabConfigRepo->updateWidgetHomeTabConfigByWorkspace(
                $homeTab,
                $widgetOrder,
                $workspace
            );
        }
        $this->om->remove($widgetHomeTabConfig);
        $this->om->flush();
    }

    public function changeOrderWidgetHomeTabConfig(
        WidgetHomeTabConfig $widgetHomeTabConfig,
        $direction
    )
    {
        $widgetOrder = $widgetHomeTabConfig->getWidgetOrder();
        $homeTab = $widgetHomeTabConfig->getHomeTab();
        $user = $widgetHomeTabConfig->getUser();
        $workspace = $widgetHomeTabConfig->getWorkspace();
        $newWidgetOrder = ($direction < 0) ? ($widgetOrder - 1) : ($widgetOrder + 1);

        if (is_null($user) && is_null($workspace)) {
            $lastWidgetOrder = $this->widgetHomeTabConfigRepo
                ->findOrderOfLastWidgetInAdminHomeTab($homeTab);
            $lastOrder = (count($lastWidgetOrder) > 0) ?
                $lastWidgetOrder['order_max'] :
                1;

            if ($newWidgetOrder > 0 && $newWidgetOrder <= $lastOrder) {
                $this->widgetHomeTabConfigRepo->updateAdminWidgetOrder(
                    $homeTab,
                    $newWidgetOrder,
                    $widgetOrder
                );
                $widgetHomeTabConfig->setWidgetOrder($newWidgetOrder);
                $this->om->flush();

                return $direction;
            }
        }
        elseif (is_null($workspace)) {
            $lastWidgetOrder = $this->widgetHomeTabConfigRepo
                ->findOrderOfLastWidgetInHomeTabByUser($homeTab, $user);
            $lastOrder = (count($lastWidgetOrder) > 0) ?
                $lastWidgetOrder['order_max'] :
                1;

            if ($newWidgetOrder > 0 && $newWidgetOrder <= $lastOrder) {
                $this->widgetHomeTabConfigRepo->updateWidgetOrderByUser(
                    $homeTab,
                    $newWidgetOrder,
                    $widgetOrder,
                    $user
                );
                $widgetHomeTabConfig->setWidgetOrder($newWidgetOrder);
                $this->om->flush();

                return $direction;
            }
        }
        else {
            $lastWidgetOrder = $this->widgetHomeTabConfigRepo
                ->findOrderOfLastWidgetInHomeTabByWorkspace($homeTab, $workspace);
            $lastOrder = (count($lastWidgetOrder) > 0) ?
                $lastWidgetOrder['order_max'] :
                1;

            if ($newWidgetOrder > 0 && $newWidgetOrder <= $lastOrder) {
                $this->widgetHomeTabConfigRepo->updateWidgetOrderByWorkspace(
                    $homeTab,
                    $newWidgetOrder,
                    $widgetOrder,
                    $workspace
                );
                $widgetHomeTabConfig->setWidgetOrder($newWidgetOrder);
                $this->om->flush();

                return $direction;
            }
        }

        return 0;
    }

    public function changeVisibilityWidgetHomeTabConfig(
        WidgetHomeTabConfig $widgetHomeTabConfig
    )
    {
        $isVisible = !$widgetHomeTabConfig->isVisible();
        $widgetHomeTabConfig->setVisible($isVisible);
        $this->om->flush();
    }

    public function changeLockWidgetHomeTabConfig(
        WidgetHomeTabConfig $widgetHomeTabConfig
    )
    {
        $isLocked = !$widgetHomeTabConfig->isLocked();
        $widgetHomeTabConfig->setLocked($isLocked);
        $this->om->flush();
    }

    public function generateCopyOfAdminWorkspaceHomeTabs(
        AbstractWorkspace $workspace
    )
    {
        $adminHomeTabConfigs = $this->homeTabConfigRepo
            ->findAdminWorkspaceHomeTabConfigs();

        foreach ($adminHomeTabConfigs as $adminHomeTabConfig) {
            // Create HomeTab
            $adminHomeTab = $adminHomeTabConfig->getHomeTab();
            $homeTab = new HomeTab();
            $homeTab->setName($adminHomeTab->getName());
            $homeTab->setType('workspace');
            $homeTab->setWorkspace($workspace);
            $this->om->persist($homeTab);
            $this->om->flush();

            // Create HomeTabConfig
            $homeTabConfig = new HomeTabConfig();
            $homeTabConfig->setHomeTab($homeTab);
            $homeTabConfig->setWorkspace($workspace);
            $homeTabConfig->setType('workspace');
            $homeTabConfig->setVisible($adminHomeTabConfig->isVisible());
            $homeTabConfig->setLocked(false);
            $homeTabConfig->setTabOrder($adminHomeTabConfig->getTabOrder());
            $this->om->persist($homeTabConfig);

            // Create WidgetHomeTabConfig
            $adminWidgetHomeTabConfigs = $this->widgetHomeTabConfigRepo
                ->findAdminWidgetConfigs($adminHomeTab);

            foreach ($adminWidgetHomeTabConfigs as $adminWidgetHomeTabConfig) {
                $widgetHomeTabConfig = new WidgetHomeTabConfig();
                $widgetHomeTabConfig->setHomeTab($homeTab);
                $widgetHomeTabConfig->setWidgetInstance(
                    $adminWidgetHomeTabConfig->getWidgetInstance()
                );
                $widgetHomeTabConfig->setWorkspace($workspace);
                $widgetHomeTabConfig->setType('workspace');
                $widgetHomeTabConfig->setVisible(
                    $adminWidgetHomeTabConfig->isVisible()
                );
                $widgetHomeTabConfig->setLocked(false);
                $widgetHomeTabConfig->setWidgetOrder(
                    $adminWidgetHomeTabConfig->getWidgetOrder()
                );
                $this->om->persist($widgetHomeTabConfig);
            }

            $this->om->flush();
        }
    }

    /**
     * HomeTabRepository access methods
     */

    public function getHomeTabById($homeTabId)
    {
        return $this->homeTabRepo->findOneById($homeTabId);
    }

    public function getHomeTabByIdAndWorkspace(
        $homeTabId,
        AbstractWorkspace $workspace
    )
    {
        return $this->homeTabRepo->findOneBy(
            array('id' => $homeTabId, 'workspace' => $workspace)
        );
    }

    /**
     * HomeTabConfigRepository access methods
     */

    public function getAdminDesktopHomeTabConfigs()
    {
        return $this->homeTabConfigRepo
            ->findAdminDesktopHomeTabConfigs();
    }

    public function getAdminWorkspaceHomeTabConfigs()
    {
        return $this->homeTabConfigRepo
            ->findAdminWorkspaceHomeTabConfigs();
    }

    public function getAdminDesktopHomeTabConfigByHomeTab(HomeTab $homeTab)
    {
        return $this->homeTabConfigRepo
            ->findAdminDesktopHomeTabConfigByHomeTab($homeTab);
    }

    public function getDesktopHomeTabConfigsByUser(User $user)
    {
        return $this->homeTabConfigRepo
            ->findDesktopHomeTabConfigsByUser($user);
    }

    public function getWorkspaceHomeTabConfigsByWorkspace(
        AbstractWorkspace $workspace
    )
    {
        return $this->homeTabConfigRepo
            ->findWorkspaceHomeTabConfigsByWorkspace($workspace);
    }

    public function getVisibleAdminDesktopHomeTabConfigs()
    {
        return $this->homeTabConfigRepo
            ->findVisibleAdminDesktopHomeTabConfigs();
    }

    public function getVisibleAdminWorkspaceHomeTabConfigs()
    {
        return $this->homeTabConfigRepo
            ->findVisibleAdminWorkspaceHomeTabConfigs();
    }

    public function getVisibleDesktopHomeTabConfigsByUser(User $user)
    {
        return $this->homeTabConfigRepo
            ->findVisibleDesktopHomeTabConfigsByUser($user);
    }

    public function getVisibleWorkspaceHomeTabConfigsByWorkspace(
        AbstractWorkspace $workspace
    )
    {
        return $this->homeTabConfigRepo
            ->findVisibleWorkspaceHomeTabConfigsByWorkspace($workspace);
    }

    public function getOrderOfLastDesktopHomeTabConfigByUser(User $user)
    {
        return $this->homeTabConfigRepo
            ->findOrderOfLastDesktopHomeTabByUser($user);
    }

    public function getOrderOfLastWorkspaceHomeTabConfigByWorkspace(
        AbstractWorkspace $workspace
    )
    {
        return $this->homeTabConfigRepo
            ->findOrderOfLastWorkspaceHomeTabByWorkspace($workspace);
    }

    public function getOrderOfLastAdminDesktopHomeTabConfig()
    {
        return $this->homeTabConfigRepo->findOrderOfLastAdminDesktopHomeTab();
    }

    public function getOrderOfLastAdminWorkspaceHomeTabConfig()
    {
        return $this->homeTabConfigRepo->findOrderOfLastAdminWorkspaceHomeTab();
    }

    public function getHomeTabConfigByHomeTabAndWorkspace(
        HomeTab $homeTab,
        AbstractWorkspace $workspace
    )
    {
        return $this->homeTabConfigRepo->findOneBy(
            array('homeTab' => $homeTab, 'workspace' => $workspace)
        );
    }

    public function getHomeTabConfigByHomeTabAndUser(HomeTab $homeTab, User $user)
    {
        return $this->homeTabConfigRepo->findOneBy(
            array('homeTab' => $homeTab, 'user' => $user)
        );
    }

    /**
     * WidgetHomeTabConfigRepository access methods
     */

    public function getAdminWidgetConfigs(HomeTab $homeTab)
    {
        return $this->widgetHomeTabConfigRepo->findAdminWidgetConfigs($homeTab);
    }

    public function getVisibleAdminWidgetConfigs(HomeTab $homeTab)
    {
        return $this->widgetHomeTabConfigRepo->findVisibleAdminWidgetConfigs($homeTab);
    }

    public function getWidgetConfigsByUser(HomeTab $homeTab, User $user)
    {
        return $this->widgetHomeTabConfigRepo
            ->findWidgetConfigsByUser($homeTab, $user);
    }

    public function getVisibleWidgetConfigsByUser(HomeTab $homeTab, User $user)
    {
        return $this->widgetHomeTabConfigRepo
            ->findVisibleWidgetConfigsByUser($homeTab, $user);
    }

    public function getWidgetConfigsByWorkspace(
        HomeTab $homeTab,
        AbstractWorkspace $workspace
    )
    {
        return $this->widgetHomeTabConfigRepo
            ->findWidgetConfigsByWorkspace($homeTab, $workspace);
    }

    public function getVisibleWidgetConfigsByWorkspace(
        HomeTab $homeTab,
        AbstractWorkspace $workspace
    )
    {
        return $this->widgetHomeTabConfigRepo
            ->findVisibleWidgetConfigsByWorkspace($homeTab, $workspace);
    }

    public function getOrderOfLastWidgetInAdminHomeTab(HomeTab $homeTab)
    {
        return $this->widgetHomeTabConfigRepo
            ->findOrderOfLastWidgetInAdminHomeTab($homeTab);
    }

    public function getOrderOfLastWidgetInHomeTabByUser(
        HomeTab $homeTab,
        User $user
    )
    {
        return $this->widgetHomeTabConfigRepo
            ->findOrderOfLastWidgetInHomeTabByUser($homeTab, $user);
    }

    public function getOrderOfLastWidgetInHomeTabByWorkspace(
        HomeTab $homeTab,
        AbstractWorkspace $workspace
    )
    {
        return $this->widgetHomeTabConfigRepo
            ->findOrderOfLastWidgetInHomeTabByWorkspace($homeTab, $workspace);
    }

    public function getUserAdminWidgetHomeTabConfig(
        HomeTab $homeTab,
        WidgetInstance $widgetInstance,
        User $user
    )
    {
        return $this->widgetHomeTabConfigRepo->findUserAdminWidgetHomeTabConfig(
            $homeTab,
            $widgetInstance,
            $user
        );
    }
}
