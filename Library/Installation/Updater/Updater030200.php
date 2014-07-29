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

use Symfony\Component\DependencyInjection\ContainerInterface;
use Claroline\CoreBundle\Library\Utilities\ClaroUtilities;

class Updater030200
{
    private $em;
    private $logger;
    private $ut;
    
    public function __construct(
        ContainerInterface $container
    )
    {
        $this->em = $container->get('doctrine.orm.entity_manager');
        $this->ut = $container->get('claroline.utilities.misc');
    }

    public function postUpdate()
    {
        $this->log('Setting user exchangeToken...');
        $userRepo = $this->em->getRepository('ClarolineCoreBundle:User');
        $allUser = $userRepo->findAll();
        for ($i = 0, $count = count($allUser); $i < $count; $i++) {
            $allUser[$i]->generateNewToken();
            $this->testFlush($i);
        }
        $this->em->flush();
                
        $this->log('Setting resourceNode hashname...');
        $resourceNodeRepo = $this->em->getRepository('ClarolineCoreBundle:Resource\ResourceNode');
        for ($i, $count = count($resourceNodeRepo); $i < $count; $i++) {
            $resourceNode[$i]->setNodeHashName($this->ut->generateGuid());
            $this->testFlush($i);
        }
        $this->em->flush();
    }

    private function testFlush($i)
    {
        if ($i % 50 === 0) {
            $this->em->flush();
        }
    }
    
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    private function log($message)
    {
        if ($log = $this->logger) {
            $log('    ' . $message);
        }
    }
}
