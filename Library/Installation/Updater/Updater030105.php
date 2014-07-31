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
use Symfony\Component\Yaml\Yaml;

class Updater030105
{
    private $container;
    private $logger;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function postUpdate()
    {
        $ds = DIRECTORY_SEPARATOR;
        $parametersFile = $this->container->getParameter('kernel.root_dir') . $ds . 'config/parameters.yml';
        $data = Yaml::parse($parametersFile);
        $data['parameters']['configurable_maintenance'] = true;
        file_put_contents($parametersFile, Yaml::dump($data));
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