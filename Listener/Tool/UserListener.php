<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Listener\Tool;

use JMS\DiExtraBundle\Annotation as DI;
use Claroline\CoreBundle\Event\DisplayToolEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @DI\Service("workspace_role_tool_config_listener")
 */
class UserListener
{
    /**
     * @DI\InjectParams({
     *     "requeststack"   = @DI\Inject("request_stack"),
     *     "ed"             = @DI\Inject("http_kernel"),
     * })
     */
    public function __construct(RequestStack $requeststack, HttpKernelInterface $httpKernel)
    {
        $this->request = $requeststack->getCurrentRequest();
        $this->httpKernel = $httpKernel;
    }

    /**
     * @DI\Observe("open_tool_workspace_users")
     *
     * @param DisplayToolEvent $event
     */
    public function onDisplay(DisplayToolEvent $event)
    {
        if (!$this->request) {
            throw new \Exception("There is no request");
        }

        $subRequest = $this->request->duplicate(
            array(),
            null,
            array(
                '_controller' => 'ClarolineCoreBundle:Tool\Roles:usersList',
                'workspace' => $event->getWorkspace(),
                'page' => 1,
                'search' => '',
                'max' => 50,
                'order' => 'id'
            )
        );
        $response = $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
        $event->setContent($response->getContent());
    }
}
