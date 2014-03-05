<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Controller\Badge;

use Claroline\CoreBundle\Entity\Badge\Badge;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use JMS\SecurityExtraBundle\Annotation as SEC;

/**
 * Controller of the badges.
 *
 * @Route("/badge")
 */
class FrontController extends Controller
{
    /**
     * @Route("/{slug}", name="claro_view_badge")
     * @ParamConverter("badge", converter="badge_converter")
     *
     * @SEC\Secure(roles="ROLE_USER")
     *
     * @Template()
     */
    public function viewAction(Badge $badge)
    {
        /** @var \Claroline\CoreBundle\Library\Configuration\PlatformConfigurationHandler $platformConfigHandler */
        $platformConfigHandler = $this->get('claroline.config.platform_config_handler');

        $badge->setLocale($platformConfigHandler->getParameter('locale_language'));

        return array(
            'badge' => $badge
        );
    }
}
