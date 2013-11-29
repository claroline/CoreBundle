<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Claroline\KernelBundle\Bundle\AutoConfigurableInterface;
use Claroline\KernelBundle\Bundle\ConfigurationProviderInterface;
use Claroline\KernelBundle\Bundle\ConfigurationBuilder;
use Claroline\InstallationBundle\Bundle\InstallableBundle;
use Claroline\CoreBundle\Library\Installation\AdditionalInstaller;

class ClarolineCoreBundle extends InstallableBundle implements AutoConfigurableInterface, ConfigurationProviderInterface
{
    public function supports($environment)
    {
        return in_array($environment, array('prod', 'dev', 'test'));
    }

    public function getConfiguration($environment)
    {
        $config = new ConfigurationBuilder();
        $configFile = $environment === 'test' ? 'config_test.yml' : 'config.yml';

        return $config
            ->addContainerResource(__DIR__ . "/Resources/config/app/{$configFile}")
            ->addRoutingResource(__DIR__ . '/Resources/config/routing.yml');
    }

    public function suggestConfigurationFor(Bundle $bundle, $environment)
    {
        $bundleClass = get_class($bundle);
        $config = new ConfigurationBuilder();

        // simple container configuration, same for every environment
        $simpleConfigs = array(
            'Symfony\Bundle\SecurityBundle\SecurityBundle'               => 'security',
            'Symfony\Bundle\TwigBundle\TwigBundle'                       => 'twig',
            'Symfony\Bundle\AsseticBundle\AsseticBundle'                 => 'assetic',
            'JMS\DiExtraBundle\JMSDiExtraBundle'                         => 'jms_di_extra',
            'JMS\SecurityExtraBundle\JMSSecurityExtraBundle'             => 'jms_security_extra',
            'Zenstruck\Bundle\FormBundle\ZenstruckFormBundle'            => 'zenstruck_form',
            'Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle' => 'stof_doctrine_extensions',
            'BeSimple\SsoAuthBundle\BeSimpleSsoAuthBundle'               => 'sso',
            'Stfalcon\Bundle\TinymceBundle\StfalconTinymceBundle'        => 'stfalcon_tinymce',
            'IDCI\Bundle\ExporterBundle\IDCIExporterBundle'              => 'idci_exporter'
        );
        // one configuration file for every standard environment (prod, dev, test)
        $envConfigs = array(
            'Symfony\Bundle\FrameworkBundle\FrameworkBundle'     => 'framework',
            'Symfony\Bundle\MonologBundle\MonologBundle'         => 'monolog',
            'Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle' => 'swiftmailer',
            'Doctrine\Bundle\DoctrineBundle\DoctrineBundle'      => 'doctrine'
        );

        if (isset($simpleConfigs[$bundleClass])) {
            return $config->addContainerResource($this->buildPath($simpleConfigs[$bundleClass]));
        } elseif (isset($envConfigs[$bundleClass])) {
            if (in_array($environment, array('prod', 'dev', 'test'))) {
                return $config->addContainerResource($this->buildPath("{$envConfigs[$bundleClass]}_{$environment}"));
            }
        } elseif ($bundle instanceof \Bazinga\ExposeTranslationBundle\BazingaExposeTranslationBundle) {
            return $config->addRoutingResource($this->buildPath('bazinga_routing'));
        } elseif (in_array($environment, array('dev', 'test'))) {
            if ($bundle instanceof \Symfony\Bundle\WebProfilerBundle\WebProfilerBundle) {
                return $config
                    ->addContainerResource($this->buildPath('web_profiler'))
                    ->addRoutingResource($this->buildPath('web_profiler_routing'));
            } elseif ($bundle instanceof \Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle) {
                return $config;
            }
        }

        return $config;
    }

    public function getRequiredFixturesDirectory($environment)
    {
        return $environment !== 'test' ? 'DataFixtures/Required' : null;
    }

    public function getOptionalFixturesDirectory($environment)
    {
        return $environment !== 'test' ? 'DataFixtures/Demo' : null;
    }

    public function getAdditionalInstaller()
    {
        return new AdditionalInstaller();
    }

    private function buildPath($file, $folder = 'suggested')
    {
        return __DIR__ . "/Resources/config/{$folder}/{$file}.yml";
    }
}
