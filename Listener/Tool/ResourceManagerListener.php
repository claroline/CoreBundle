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
use Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace;
use Claroline\CoreBundle\Event\DisplayToolEvent;
use Claroline\CoreBundle\Event\ConfigureWorkspaceToolEvent;
use Claroline\CoreBundle\Manager\ResourceManager;
use Claroline\CoreBundle\Manager\RightsManager;
use Claroline\CoreBundle\Manager\WorkspaceManager;
use Claroline\CoreBundle\Manager\WorkspaceTagManager;
use Claroline\CoreBundle\Event\StrictDispatcher;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @DI\Service()
 */
class ResourceManagerListener
{
    private $resourceManager;
    private $rightsManager;
    private $workspaceManager;

    /**
     * @DI\InjectParams({
     *     "em"                     = @DI\Inject("doctrine.orm.entity_manager"),
     *     "ed"                     = @DI\Inject("claroline.event.event_dispatcher"),
     *     "templating"             = @DI\Inject("templating"),
     *     "manager"                = @DI\Inject("claroline.manager.resource_manager"),
     *     "sc"                     = @DI\Inject("security.context"),
     *     "requeststack"           = @DI\Inject("request_stack"),
     *     "resourceManager"        = @DI\Inject("claroline.manager.resource_manager"),
     *     "rightsManager"          = @DI\Inject("claroline.manager.rights_manager"),
     *     "workspaceManager"       = @DI\Inject("claroline.manager.workspace_manager"),
     *     "workspaceTagManager"    = @DI\Inject("claroline.manager.workspace_tag_manager")
     * })
     */
    public function __construct(
        $em,
        StrictDispatcher $ed,
        $templating,
        $manager,
        $sc,
        RequestStack $requeststack,
        ResourceManager $resourceManager,
        RightsManager $rightsManager,
        WorkspaceManager $workspaceManager,
        WorkspaceTagManager $workspaceTagManager
    )
    {
        $this->em = $em;
        $this->ed = $ed;
        $this->templating = $templating;
        $this->manager = $manager;
        $this->sc = $sc;
        $this->request = $requeststack->getCurrentRequest();
        $this->resourceManager = $resourceManager;
        $this->rightsManager = $rightsManager;
        $this->workspaceManager = $workspaceManager;
        $this->workspaceTagManager = $workspaceTagManager;
    }

    /**
     * @DI\Observe("open_tool_workspace_resource_manager")
     *
     * @param DisplayToolEvent $event
     */
    public function onDisplayWorkspaceResouceManager(DisplayToolEvent $event)
    {
        $event->setContent($this->resourceWorkspace($event->getWorkspace()->getId()));
    }

    /**
     * @DI\Observe("configure_workspace_tool_resource_manager")
     *
     * @param ConfigureWorkspaceToolEvent $event
     */
    public function onDisplayWorkspaceResourceConfiguration(ConfigureWorkspaceToolEvent $event)
    {
        $event->setContent($this->workspaceResourceRightsForm($event->getWorkspace()));
    }

    /**
     * @DI\Observe("open_tool_desktop_resource_manager")
     *
     * @param DisplayToolEvent $event
     */
    public function onDisplayDesktopResourceManager(DisplayToolEvent $event)
    {
        $event->setContent($this->resourceDesktop());
    }

    /**
     * Renders the resources page with its layout.
     *
     * @param integer $workspaceId
     *
     * @return string
     */
    public function resourceWorkspace($workspaceId)
    {
        if (!$this->request) {
            throw new \Exception("There is no request");
        }

        $breadcrumbsIds = $this->request->query->get('_breadcrumbs');

        if ($breadcrumbsIds != null) {
            $ancestors = $this->manager->getByIds($breadcrumbsIds);

            if (!$this->manager->isPathValid($ancestors)) {
                throw new \Exception('Breadcrumbs invalid');
            };
        } else {
            $ancestors = array();
        }
        $path = array();

        foreach ($ancestors as $ancestor) {
            $path[] = $this->manager->toArray($ancestor);
        }

        $jsonPath = json_encode($path);

        $workspace = $this->workspaceManager->getWorkspaceById($workspaceId);
        $directoryId = $this->resourceManager->getWorkspaceRoot($workspace)->getId();
        $resourceTypes = $this->em->getRepository('ClarolineCoreBundle:Resource\ResourceType')
            ->findAll();

        return $this->templating->render(
            'ClarolineCoreBundle:Tool\workspace\resource_manager:resources.html.twig', array(
                'workspace' => $workspace,
                'directoryId' => $directoryId,
                'resourceTypes' => $resourceTypes,
                'jsonPath' => $jsonPath,
                'maxPostSize' => ini_get('post_max_size'),
                'resourceZoom' => $this->getZoom()
             )
        );
    }

    /**
     * Displays the resource manager.
     *
     * @return string
     */
    public function resourceDesktop()
    {
        $resourceTypes = $this->em->getRepository('ClarolineCoreBundle:Resource\ResourceType')->findAll();

        return $this->templating->render(
            'ClarolineCoreBundle:Tool\desktop\resource_manager:resources.html.twig',
            array(
                'resourceTypes' => $resourceTypes,
                'maxPostSize' => ini_get('post_max_size'),
                'resourceZoom' => $this->getZoom()
            )
        );
    }

    private function workspaceResourceRightsForm(AbstractWorkspace $workspace)
    {
        if (!$this->sc->isGranted('parameters', $workspace)) {
            throw new AccessDeniedException();
        }
        $resource = $this->resourceManager->getWorkspaceRoot($workspace);
        $roleRights = $this->rightsManager->getNonAdminRights($resource);

        $datas = $this->workspaceTagManager->getDatasForWorkspaceList(true);

        return $this->templating->render(
            'ClarolineCoreBundle:Tool\workspace\resource_manager:resourcesRights.html.twig',
            array(
                'workspace' => $workspace,
                'resource' => $resource,
                'resourceRights' => $roleRights,
                'workspaces' => $datas['workspaces'],
                'isDir' => true,
                'tags' => $datas['tags'],
                'tagWorkspaces' => $datas['tagWorkspaces'],
                'hierarchy' => $datas['hierarchy'],
                'rootTags' => $datas['rootTags'],
                'displayable' => $datas['displayable'],
                'workspaceRoles' => $datas['workspaceRoles']
            )
        );
    }

    public function getZoom($zoom = "zoom100")
    {
        if (!$this->request) {
            throw new \Exception("There is no request");
        }

        if ($this->request->getSession()->get('resourceZoom')) {
            $zoom = $this->request->getSession()->get('resourceZoom');
        }

        return $zoom;
    }
}
