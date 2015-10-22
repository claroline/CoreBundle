<?php
/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Library\Installation\Updater;

use Claroline\InstallationBundle\Updater\Updater;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Claroline\CoreBundle\Library\Utilities\FileSystem;

class Updater060601 extends Updater
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function postUpdate()
    {
        $fs = new FileSystem();
        $this->log('Creating ' . $this->container->getParameter('claroline.param.authentication_directory') . 'oauth' . '...');
        $fs->mkdir($this->container->getParameter('claroline.param.authentication_directory') . 'oauth');

        //@todo move this to the ldapbundle
        //removing ldapbundle if it exists from the configuration.
        $om = $this->container->get('claroline.persistence.object_manager');
        $adminTool = $om->getRepository('ClarolineCoreBundle:Tool\AdminTool')
            ->findOneByName('LDAP');

        if ($adminTool) {
            $this->log('Removing ldap admin tool...');
            $om->remove($adminTool);
            $om->flush();
        }
    }
}

