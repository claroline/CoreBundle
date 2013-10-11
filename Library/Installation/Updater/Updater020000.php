<?php

namespace Claroline\CoreBundle\Library\Installation\Updater;

use Claroline\CoreBundle\Entity\Home\HomeTab;
use Claroline\CoreBundle\Entity\Widget\Widget;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Symfony\Component\Filesystem\Filesystem;

class Updater020000
{
    private $container;
    private $conn;
    private $translator;
    private $logger;

    public function __construct($container)
    {
        $this->container = $container;
        $this->conn = $container->get('doctrine.dbal.default_connection');
        $this->translator = $container->get('translator');
        $locale = $container->get('claroline.config.platform_config_handler')
            ->getParameter('locale_language');
        $this->translator->setLocale($locale);
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    public function log($message)
    {
        if ($this->logger) {
            $log = $this->logger;
            $log($message);
        }
    }

    public function preUpdate()
    {
        $this->addLogosAndIcons();
        $this->copyWidgetHomeTabConfigTable();
    }

    public function postUpdate()
    {
        $this->initWidgets();
        $this->updateWidgetsDatas();
        $this->updateTextWidgets();
        $this->updateWidgetHomeTabConfigsDatas();
        $this->updateAdminWorkspaceHomeTabDatas();
        $this->createWorkspacesListWidget();
        $this->updateHomeTool();
        $this->dropTables();
        $this->dropWidgetHomeTabConfigTableCopy();
    }

    private function initWidgets()
    {
        $this->log('Updating claro_widget table ...');
        $this->conn->query("
            UPDATE claro_widget
            SET is_displayable_in_workspace = true,
                is_displayable_in_desktop = true
            WHERE name = 'core_resource_logger'
            OR name = 'simple_text'
            OR name = 'claroline_announcement_widget'
            OR name = 'claroline_rssreader'
        ");
        $this->conn->query("
            UPDATE claro_widget
            SET is_displayable_in_workspace = false,
                is_displayable_in_desktop = true
            WHERE name = 'my_workspaces'
        ");
    }

    private function updateTextWidgets()
    {
        $this->log('Migrating simple text widget data ...');

        //text_widget_id
        $result = $this->conn->query(
            "SELECT id FROM claro_widget WHERE name = 'simple_text'
        ");
        $widget = $result->fetch();
        $widgetId = $widget['id'];
        $wconfigs = $this->conn->query(
            "SELECT * FROM simple_text_workspace_widget_config"
        );

        foreach ($wconfigs as $config) {
            if (!$config['is_default']) {
                $name = $this->conn->quote($this->translator->trans('simple_text', array(), 'widget'));
                $query = "
                    INSERT INTO claro_widget_instance (workspace_id, user_id, widget_id, is_admin, is_desktop, name)
                    VALUES ({$config['workspace_id']}, null, {$widgetId}, false, false, {$name})
                ";
                $this->conn->query($query);
            }

            $query = "
                SELECT * FROM claro_widget_instance
                WHERE workspace_id = {$config['workspace_id']}
                AND widget_id = {$widgetId}
            ";
            $instance = $this->conn->query($query)->fetch();
            $this->conn->query("
                INSERT INTO claro_simple_text_widget_config (content, widgetInstance_id)
                VALUES (". $this->conn->quote($config['content']) . ", {$instance['id']})
            ");
        }
    }

    private function updateWidgetsDatas()
    {
        $this->log('Migrating widgets display tables...');
        $select = "
            SELECT instance. * , widget.name AS widget_name
            FROM claro_widget_display instance
            RIGHT JOIN claro_widget widget ON instance.widget_id = widget.id
            WHERE parent_id IS NOT NULL
        ";
        $rows =  $this->conn->query($select);

        foreach ($rows as $row) {
            $isAdmin = $row['parent_id'] == NULL ? 'true': 'false';
            $wsId = $row['workspace_id'] ? $row['workspace_id']: 'null';
            $userId = $row['user_id'] ? $row['user_id']: 'null';
            $name = $this->conn->quote($this->translator->trans($row['widget_name'], array(), 'widget'));
            $query = "
                INSERT INTO claro_widget_instance (workspace_id, user_id, widget_id, is_admin, is_desktop, name)
                VALUES ({$wsId}, {$userId}, {$row['widget_id']}, {$isAdmin}, {$row['is_desktop']}, {$name})
            ";
            $this->conn->query($query);
        }
    }

    private function dropTables()
    {
        $this->log('Drop useless tables...');
        $this->conn->query('DROP table claro_widget_display');
        $this->conn->query('DROP TABLE simple_text_dekstop_widget_config');
        $this->conn->query('DROP TABLE simple_text_workspace_widget_config');
        $this->conn->query('DROP TABLE claro_log_workspace_widget_config');
        $this->conn->query('DROP TABLE claro_log_desktop_widget_config');
    }

    private function updateWidgetHomeTabConfigsDatas()
    {
        $this->log('Updating home tabs...');
        $widgetHomeTabConfigsReq = "
            SELECT *
            FROM claro_widget_home_tab_config_temp
            ORDER BY id
        ";
        $rows =  $this->conn->query($widgetHomeTabConfigsReq);

        foreach ($rows as $row) {
            $widgetHomeTabConfigId = $row['id'];
            $homeTabId = $row['home_tab_id'];
            $widgetId = $row['widget_id'];

            $homeTabsReq = "
                SELECT *
                FROM claro_home_tab
                WHERE id = {$homeTabId}
            ";
            $homeTab = $this->conn->query($homeTabsReq)->fetch();
            $homeTabType = $homeTab['type'];

            $widgetInstanceReq = "
                SELECT *
                FROM claro_widget_instance
                WHERE widget_id = {$widgetId}
                AND is_admin = false
            ";

            if ($homeTabType === 'admin_desktop' || $homeTabType === 'desktop') {
                $widgetInstanceReq .= " AND is_desktop = true";
            } else {
                $widgetInstanceReq .= " AND is_desktop = false";
            }

            if (is_null($row['user_id'])) {
                $widgetInstanceReq .= " AND user_id IS NULL";
            } else {
                $widgetInstanceReq .= " AND user_id = {$row['user_id']}";
            }

            if (is_null($row['workspace_id'])) {
                $widgetInstanceReq .= " AND workspace_id IS NULL";
            } else {
                $widgetInstanceReq .= " AND workspace_id = {$row['workspace_id']}";
            }

            $widgetInstances = $this->conn->query($widgetInstanceReq);
            $widgetInstance = $widgetInstances->fetch();

            if ($widgetInstance) {
                $widgetInstanceId = $widgetInstance['id'];
                $updateReq = "
                    UPDATE claro_widget_home_tab_config
                    SET widget_instance_id = {$widgetInstanceId}
                    WHERE id = {$widgetHomeTabConfigId}
                ";
                $this->conn->query($updateReq);
            } else {
                $deleteReq = "
                    DELETE FROM claro_widget_home_tab_config
                    WHERE id = {$widgetHomeTabConfigId}
                ";
                $this->conn->query($deleteReq);
            }
        }
    }

    private function createWorkspacesListWidget()
    {
        $this->log('Writing temporary tables...');
        $em = $this->container->get('doctrine.orm.entity_manager');

        try {
            $workspaceWidget = $em->getRepository('ClarolineCoreBundle:Widget\Widget')
                ->findOneByName('my_workspaces');

            if (is_null($workspaceWidget)) {
                $this->logger();
                $widget = new Widget();
                $widget->setName('my_workspaces');
                $widget->setConfigurable(false);
                $widget->setIcon('fake/icon/path');
                $widget->setPlugin(null);
                $widget->setExportable(false);
                $widget->setDisplayableInDesktop(true);
                $widget->setDisplayableInWorkspace(false);
                $em->persist($widget);
                $em->flush();
            }
        }
        catch (MappingException $e) {
            $this->log('A MappingException has been thrown while trying to get Widget repository');
        }
    }

    private function updateAdminWorkspaceHomeTabDatas()
    {
        $this->log('Updating admin tabs...');
        $em = $this->container->get('doctrine.orm.entity_manager');

        try {
            $homeTabConfigRepo = $em->getRepository('ClarolineCoreBundle:Home\HomeTabConfig');
            $widgetHTCRepo = $em->getRepository('ClarolineCoreBundle:Widget\WidgetHomeTabConfig');

            $homeTabConfigs = $homeTabConfigRepo->findWorkspaceHomeTabConfigsByAdmin();

            foreach ($homeTabConfigs as $homeTabConfig) {
                $homeTab = $homeTabConfig->getHomeTab();
                $workspace = $homeTabConfig->getWorkspace();

                $newHomeTab = new HomeTab();
                $newHomeTab->setType('workspace');
                $newHomeTab->setWorkspace($workspace);
                $newHomeTab->setName($homeTab->getName());
                $em->persist($newHomeTab);
                $em->flush();

                $homeTabConfig->setType('workspace');
                $homeTabConfig->setHomeTab($newHomeTab);
                $lastOrder = $homeTabConfigRepo
                    ->findOrderOfLastWorkspaceHomeTabByWorkspace($workspace);

                if (is_null($lastOrder['order_max'])) {
                    $homeTabConfig->setTabOrder(1);
                }else {
                    $homeTabConfig->setTabOrder($lastOrder['order_max'] + 1);
                }

                $widgetHomeTabConfigs = $widgetHTCRepo
                    ->findWidgetConfigsByWorkspace($homeTab, $workspace);

                foreach ($widgetHomeTabConfigs as $widgetHomeTabConfig) {
                    $widgetHomeTabConfig->setHomeTab($newHomeTab);
                }
                $em->flush();
            }
        }
        catch (MappingException $e) {
            $this->log('A MappingException has been thrown while trying to get HomeTabConfig or WidgetHomeTabConfig repository');
        }
    }

    private function copyWidgetHomeTabConfigTable()
    {
        $this->conn->query('
            CREATE TABLE claro_widget_home_tab_config_temp
            AS (SELECT * FROM claro_widget_home_tab_config)
        ');
    }

    private function dropWidgetHomeTabConfigTableCopy()
    {
        $this->conn->query('DROP TABLE claro_widget_home_tab_config_temp');
    }

    private function updateHomeTool()
    {
        $this->log('Updating tool home...');
        $this->conn->query("
            UPDATE claro_tools
            SET is_configurable_in_workspace = false,
            is_configurable_in_desktop = false
            WHERE name = 'home'
        ");
    }

    private function addLogosAndIcons()
    {
        $filesystem = new Filesystem();
        $imgDir = __DIR__ . '/../../../Resources/public/images';
        $webDir = __DIR__ . '/../../../../../../../../web';
        $filesystem->mirror("{$imgDir}/logos", "{$webDir}/uploads/logos");
        $filesystem->copy("{$imgDir}/ico/favicon.ico", "{$webDir}/favicon.ico", true);
        $filesystem->copy("{$imgDir}/ico/apple-touch-icon.png", "{$webDir}/apple-touch-icon.png", true);
    }
}
