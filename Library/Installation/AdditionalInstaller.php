<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Library\Installation;

use Claroline\CoreBundle\Library\Installation\Updater\MaintenancePageUpdater;
use Claroline\CoreBundle\Library\Workspace\TemplateBuilder;
use Claroline\InstallationBundle\Additional\AdditionalInstaller as BaseInstaller;
use Symfony\Bundle\SecurityBundle\Command\InitAclCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class AdditionalInstaller extends BaseInstaller
{
    private $logger;

    public function __construct()
    {
        $self = $this;
        $this->logger = function ($message) use ($self) {
            $self->log($message);
        };
    }

    public function preInstall()
    {
        $this->setLocale();
        $this->buildDefaultTemplate();
    }

    public function preUpdate($currentVersion, $targetVersion)
    {
        $maintenanceUpdater = new Updater\WebUpdater($this->container->getParameter('kernel.root_dir'));
        $maintenanceUpdater->preUpdate();

        $this->setLocale();

        switch (true) {
            case version_compare($currentVersion, '2.0', '<') && version_compare($targetVersion, '2.0', '>='):
                $updater = new Updater\Updater020000($this->container);
                $updater->setLogger($this->logger);
                $updater->preUpdate();
                break;
            case version_compare($currentVersion, '2.9.0', '<'):
                $updater = new Updater\Updater020900($this->container);
                $updater->setLogger($this->logger);
                $updater->preUpdate();
                break;
            case version_compare($currentVersion, '3.0.0', '<'):
                $updater = new Updater\Updater030000($this->container);
                $updater->setLogger($this->logger);
                $updater->preUpdate();
                break;
        }
    }

    public function postUpdate($currentVersion, $targetVersion)
    {
        $this->setLocale();
        switch (true) {
            case version_compare($currentVersion, '2.0', '<')  && version_compare($targetVersion, '2.0', '>='):
                $updater = new Updater\Updater020000($this->container);
                $updater->setLogger($this->logger);
                $updater->postUpdate();
                break;
            case version_compare($currentVersion, '2.1.2', '<'):
                $updater = new Updater\Updater020102($this->container);
                $updater->setLogger($this->logger);
                $updater->postUpdate();
                break;
            case version_compare($currentVersion, '2.1.5', '<'):
                $this->log('Creating acl tables if not present...');
                $command = new InitAclCommand();
                $command->setContainer($this->container);
                $command->run(new ArrayInput(array(), new NullOutput()));
                break;
            case version_compare($currentVersion, '2.2.0', '<'):
                $updater = new Updater\Updater020200($this->container);
                $updater->setLogger($this->logger);
                $updater->postUpdate();
                break;
            case version_compare($currentVersion, '2.3.1', '<'):
                $updater = new Updater\Updater020301($this->container);
                $updater->setLogger($this->logger);
                $updater->postUpdate();
                break;
            case version_compare($currentVersion, '2.3.4', '<'):
                $updater = new Updater\Updater020304($this->container);
                $updater->setLogger($this->logger);
                $updater->postUpdate();
                break;
            case version_compare($currentVersion, '2.5.0', '<'):
                $updater = new Updater\Updater020500($this->container);
                $updater->setLogger($this->logger);
                $updater->postUpdate();
                break;
            case version_compare($currentVersion, '2.8.0', '<'):
                $updater = new Updater\Updater020800($this->container);
                $updater->setLogger($this->logger);
                $updater->postUpdate();
                break;
            case version_compare($currentVersion, '2.9.0', '<'):
                $updater = new Updater\Updater020900($this->container);
                $updater->setLogger($this->logger);
                $updater->postUpdate();
                break;
            case version_compare($currentVersion, '2.10.0', '<'):
                $updater = new Updater\Updater021000($this->container);
                $updater->setLogger($this->logger);
                $updater->postUpdate();
                break;
            case version_compare($currentVersion, '2.11.0', '<'):
                $this->buildDefaultTemplate();
                $updater = new Updater\Updater021100($this->container);
                $updater->setLogger($this->logger);
                $updater->postUpdate();
                break;
            case version_compare($currentVersion, '2.12.0', '<'):
                $this->buildDefaultTemplate();
                $updater = new Updater\Updater021200($this->container);
                $updater->setLogger($this->logger);
                $updater->postUpdate();
                break;
            case version_compare($currentVersion, '2.12.1', '<'):
                $this->buildDefaultTemplate();
                $updater = new Updater\Updater021201($this->container);
                $updater->setLogger($this->logger);
                $updater->postUpdate();
                break;
            case version_compare($currentVersion, '2.14.0', '<'):
                $this->buildDefaultTemplate();
                $updater = new Updater\Updater021400($this->container);
                $updater->setLogger($this->logger);
                $updater->postUpdate();
                break;
            case version_compare($currentVersion, '2.14.1', '<'):
                $this->buildDefaultTemplate();
                $updater = new Updater\Updater021401($this->container);
                $updater->setLogger($this->logger);
                $updater->postUpdate();
                break;
            case version_compare($currentVersion, '2.16.0', '<'):
                $this->buildDefaultTemplate();
                $updater = new Updater\Updater021600($this->container);
                $updater->setLogger($this->logger);
                $updater->postUpdate();
                break;
            case version_compare($currentVersion, '2.16.2', '<'):
                $this->buildDefaultTemplate();
                $updater = new Updater\Updater021602($this->container);
                $updater->setLogger($this->logger);
                $updater->postUpdate();
                break;
            case version_compare($currentVersion, '2.16.4', '<'):
                $this->buildDefaultTemplate();
                $updater = new Updater\Updater021604($this->container);
                $updater->setLogger($this->logger);
                $updater->postUpdate();
                break;
            case version_compare($currentVersion, '3.0.0', '<'):
                $this->buildDefaultTemplate();
                $updater = new Updater\Updater030000($this->container);
                $updater->setLogger($this->logger);
                $updater->postUpdate();
                break;
            case version_compare($currentVersion, '3.1.0', '<'):
                $this->buildDefaultTemplate();
                $updater = new Updater\Updater030100($this->container);
                $updater->setLogger($this->logger);
                $updater->postUpdate();
                break;
            case version_compare($currentVersion, '3.2.0', '<'):
                $this->buildDefaultTemplate();
                $updater = new Updater\Updater032000($this->container);
                $updater->setLogger($this->logger);
                $updater->postUpdate();
                break;
        }

        if (version_compare($currentVersion, '3.1.5', '<')) {
            $this->buildDefaultTemplate();
            $updater030105 = new Updater\Updater030105($this->container);
            $updater030105->setLogger($this->logger);
            $updater030105->postUpdate();
        }
    }

    private function setLocale()
    {
        $ch = $this->container->get('claroline.config.platform_config_handler');
        $locale = $ch->getParameter('locale_language');
        $translator = $this->container->get('translator');
        $translator->setLocale($locale);
    }

    private function buildDefaultTemplate()
    {
        $this->log('Creating default workspace template...');
        $defaultTemplatePath = $this->container->getParameter('kernel.root_dir') . '/../templates/default.zip';
        TemplateBuilder::buildDefault($defaultTemplatePath);
    }
}
