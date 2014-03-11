<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Library\Installation\Plugin;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Claroline\CoreBundle\Library\PluginBundle;
use Claroline\CoreBundle\Entity\Resource\AbstractResource;

class Configuration implements ConfigurationInterface
{
    private $plugin;
    private $listNames;
    private $listTools;
    private $updateMode;

    public function __construct(PluginBundle $plugin, array $resourceNames, array $listTools)
    {
        $this->plugin     = $plugin;
        $this->listNames  = $resourceNames;
        $this->listTools  = $listTools;
        $this->updateMode = false;
    }

    public function getConfigTreeBuilder()
    {
        $treeBuilder   = new TreeBuilder();
        $rootNode      = $treeBuilder->root('config');
        $pluginSection = $rootNode->children('plugin');
        $this->addGeneralSection($pluginSection);
        $this->addWidgetSection($pluginSection);
        $this->addResourceSection($pluginSection);
        $this->addToolSection($pluginSection);
        $this->addThemeSection($pluginSection);

        return $treeBuilder;
    }

    private function addGeneralSection($pluginSection)
    {
        $plugin     = $this->plugin;
        $pluginFqcn = get_class($plugin);
        $imgFolder  = $plugin->getImgFolder();
        $ds         = DIRECTORY_SEPARATOR;

        $pluginSection
            ->booleanNode('has_options')->end()
            ->scalarNode('icon')
                ->validate()
                    ->ifTrue(
                        function ($v) use ($plugin) {
                            return !call_user_func_array(
                                __CLASS__ . '::isIconValid',
                                array($v, $plugin)
                            );
                        }
                    )
                    ->thenInvalid($pluginFqcn . " : this file was not found ({$imgFolder}{$ds}%s)")
                ->end()
            ->end()
        ->end();
    }

    private function addResourceSection($pluginSection)
    {
        $plugin       = $this->plugin;
        $pluginFqcn   = get_class($plugin);
        $resourceFile = $plugin->getConfigFile();
        $imgFolder    = $plugin->getImgFolder();
        $listNames    = $this->listNames;
        $updateMode   = $this->isInUpdateMode();

        $pluginSection
            ->arrayNode('resources')
                ->prototype('array')
                    ->children()
                       ->scalarNode('name')
                            ->isRequired()
                                ->validate()
                                    ->ifTrue(
                                        function ($v) use ($listNames, $updateMode) {
                                            return !$updateMode && !call_user_func_array(
                                                __CLASS__ . '::isNameAlreadyExist',
                                                array($v, $listNames)
                                            );
                                        }
                                    )
                                    ->thenInvalid($pluginFqcn . " : the ressource type name already exists")
                                ->end()
                            ->end()
                       ->scalarNode('class')
                            ->isRequired()
                                ->validate()
                                    ->ifTrue(
                                        function ($v) use ($plugin) {
                                            return !call_user_func_array(
                                                __CLASS__ . '::isResourceLocationValid',
                                                array($v, $plugin)
                                            );
                                        }
                                    )
                                    ->thenInvalid($pluginFqcn . " : %s (declared in {$resourceFile}) was not found.")
                                ->end()
                                ->validate()
                                    ->ifTrue(
                                        function ($v) {
                                            return !call_user_func_array(
                                                __CLASS__ . '::isAbstractResourceExtended',
                                                array($v)
                                            );
                                        }
                                    )
                                    ->thenInvalid(
                                        $pluginFqcn . " : %s (declared in {$resourceFile}) must extend  "
                                        . "'Claroline\\CoreBundle\\Entity\\Resource\\AbstractResource'."
                                    )
                                ->end()
                            ->end()
                       ->booleanNode('is_visible')->end()    // must be removed
                       ->booleanNode('is_browsable')->end()  // must be removed
                       ->scalarNode('is_exportable')->defaultValue(false)->end()
                       ->scalarNode('icon')
                           ->validate()
                                ->ifTrue(
                                    function ($v) use ($plugin) {
                                        return !call_user_func_array(
                                            __CLASS__ . '::isResourceIconValid',
                                            array($v, $plugin)
                                        );
                                    }
                                )
                                ->thenInvalid($pluginFqcn . " : this file was not found ({$imgFolder}%s)")
                           ->end()
                       ->end()
                       ->arrayNode('actions')
                         ->prototype('array')
                            ->children()
                                ->scalarNode('name')->isRequired()->end()
                                ->scalarNode('menu_name')->end()
                            ->end()
                         ->end()
                       ->end()
                       ->arrayNode('default_rights')
                         ->prototype('array')
                            ->children()
                                ->scalarNode('name')->end()
                            ->end()
                         ->end()
                       ->end()
                    ->end()
                 ->end()
            ->end()
        ->end()->end();
    }

    private function addWidgetSection($pluginSection)
    {
        $plugin     = $this->plugin;
        $pluginFqcn = get_class($plugin);
        $imgFolder  = $plugin->getImgFolder();
        $ds         = DIRECTORY_SEPARATOR;

        $pluginSection
            ->arrayNode('widgets')
                ->prototype('array')
                    ->children()
                        ->scalarNode('name')
                        ->isRequired()->end()
                        ->booleanNode('is_configurable')->isRequired()->end()
                        ->scalarNode('is_exportable')->defaultValue(false)->end()
                        ->scalarNode('icon')
                            ->validate()
                            ->ifTrue(
                                function ($v) use ($plugin) {
                                    return !call_user_func_array(
                                        __CLASS__ . '::isIconValid',
                                        array($v, $plugin)
                                    );
                                }
                            )
                            ->thenInvalid($pluginFqcn . " : this file was not found ({$imgFolder}{$ds}%s)")
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end()->end();
    }

    private function addToolSection($pluginSection)
    {
        $tools      = $this->listTools;
        $plugin     = $this->plugin;
        $pluginFqcn = get_class($plugin);
        $updateMode = $this->isInUpdateMode();
        $pluginSection
            ->arrayNode('tools')
                ->prototype('array')
                    ->children()
                        ->scalarNode('name')
                            ->isRequired()
                                ->validate()
                                ->ifTrue(
                                    function ($v) use ($tools, $updateMode) {
                                        return !$updateMode && !call_user_func_array(
                                            __CLASS__ . '::isNameAlreadyExist',
                                            array($v, $tools)
                                        );
                                    }
                                )
                                ->thenInvalid($pluginFqcn . " : the tool name already exists")
                            ->end()
                        ->end()
                        ->booleanNode('is_displayable_in_workspace')->isRequired()->end()
                        ->booleanNode('is_displayable_in_desktop')->isRequired()->end()
                        ->scalarNode('class')->end()
                        ->scalarNode('is_exportable')->defaultValue(false)->end()
                        //@todo remove the following line later
                        ->scalarNode('has_options')->defaultValue(false)->end()
                        ->scalarNode('is_configurable_in_workspace')->defaultValue(false)->end()
                        ->scalarNode('is_configurable_in_desktop')->defaultValue(false)->end()
                        ->scalarNode('is_locked_for_admin')->defaultValue(false)->end()
                        ->scalarNode('is_anonymous_excluded')->defaultValue(true)->end()
                    ->end()
                ->end()
            ->end()
        ->end()->end();
    }

    private function addThemeSection($pluginSection)
    {
        $pluginSection
            ->arrayNode('themes')
                ->prototype('array')
                    ->children()
                      ->scalarNode('name')->isRequired()->end()
                      ->scalarNode('path')->isRequired()->end()
                    ->end()
                ->end()
            ->end()
        ->end()->end();
    }

    public static function isResourceLocationValid($v)
    {
        if (!class_exists($v)) {
            // the autoloader doesn't know the resource namespace
            $classFile = __DIR__ . '/../../../../../../plugin/' . str_replace('\\', '/', $v) . '.php';

            if (!file_exists($classFile)) {
                return false;
            }

            // force class loading (needed for next check)
            require_once $classFile;
        }

        return true;
    }

    public static function isAbstractResourceExtended($v)
    {
        return (new $v) instanceof AbstractResource;
    }

    public static function isResourceIconValid($v, $plugin)
    {
        $ds                  = DIRECTORY_SEPARATOR;
        $imgFolder           = $plugin->getImgFolder();
        $expectedImgLocation = $imgFolder . $ds . $ds . $v;

        return file_exists($expectedImgLocation);
    }

    public static function isSmallIconValid($v, $plugin)
    {
        $ds                  = DIRECTORY_SEPARATOR;
        $imgFolder           = $plugin->getImgFolder();
        $expectedImgLocation = $imgFolder . $ds . 'small' . $ds . $v;

        return file_exists($expectedImgLocation);
    }

    public static function isIconValid($v, $plugin)
    {
        $ds                  = DIRECTORY_SEPARATOR;
        $imgFolder           = $plugin->getImgFolder();
        $expectedImgLocation = $imgFolder.$ds.$v;

        return file_exists($expectedImgLocation);
    }

    public static function isNameAlreadyExist($v, $listNames)
    {
        return (!in_array($v, $listNames));
    }

    /**
     * @param $updateMode
     *
     * @return Configuration
     */
    public function setUpdateMode($updateMode)
    {
        $this->updateMode = $updateMode;

        return $this;
    }

    /**
     * @return bool
     */
    public function isInUpdateMode()
    {
        return $this->updateMode;
    }
}
