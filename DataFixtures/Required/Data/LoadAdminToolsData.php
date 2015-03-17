<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\DataFixtures\Required\Data;

use Claroline\CoreBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\Tool\AdminTool;
use Claroline\CoreBundle\DataFixtures\Required\RequiredFixture;

class LoadAdminToolsData implements RequiredFixture
{
    public function load(ObjectManager $manager)
    {
        $tools = array(
            array('platform_parameters', 'cog'),
            array('user_management', 'user'),
            array('workspace_management', 'book'),
            array('registration_to_workspace', 'book'),
            array('platform_packages', 'wrench'),
            array('home_tabs', 'th-large'),
            array('desktop_tools', 'pencil'),
            array('platform_logs', 'bars'),
            array('platform_analytics', 'bar-chart-o'),
            array('roles_management', 'users'),
            array('competence_referencial', 'graduation-cap')
            //array('competence_subscription', 'code-fork')
        );

        foreach ($tools as $tool) {
            $entity = new AdminTool();
            $entity ->setName($tool[0]);
            $entity ->setClass($tool[1]);
            $manager->persist($entity);
        }

        $manager->flush();
    }

    public function setContainer($container)
    {
        $this->container = $container;
    }
}
