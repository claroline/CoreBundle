<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Controller\Tool;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Claroline\CoreBundle\Entity\Event;
use Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace;
use Claroline\CoreBundle\Manager\RoleManager;
use Claroline\CoreBundle\Manager\AgendaManager;
use Claroline\CoreBundle\Form\Factory\FormFactory;
use Claroline\CoreBundle\Persistence\ObjectManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controller of the agenda
 */
class WorkspaceAgendaController extends Controller
{
    private $security;
    private $formFactory;
    private $om;
    private $request;
    private $rm;
    private $agendaManager;

    /**
     * @DI\InjectParams({
     *     "security"           = @DI\Inject("security.context"),
     *     "formFactory"        = @DI\Inject("claroline.form.factory"),
     *     "om"                 = @DI\Inject("claroline.persistence.object_manager"),
     *     "request"            = @DI\Inject("request"),
     *     "rm"                 =  @DI\Inject("claroline.manager.role_manager"),
     *     "agendaManager"      = @DI\Inject("claroline.manager.agenda_manager")
     * })
     */
    public function __construct(
        SecurityContextInterface $security,
        FormFactory $formFactory,
        ObjectManager $om,
        Request $request,
        RoleManager $rm,
        AgendaManager $agendaManager
    )
    {
        $this->security = $security;
        $this->formFactory = $formFactory;
        $this->om = $om;
        $this->request = $request;
        $this->rm = $rm;
        $this->agendaManager = $agendaManager;
    }

    /**
     * @EXT\Route(
     *     "/{workspaceId}/add",
     *     name="claro_workspace_agenda_add_event"
     * )
     * @EXT\Method("POST")
     * @EXT\ParamConverter(
     *      "workspace",
     *      class="ClarolineCoreBundle:Workspace\AbstractWorkspace",
     *      options={"id" = "workspaceId", "strictId" = true}
     * )
     *
     * @param AbstractWorkspace $workspace
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addEventAction(AbstractWorkspace $workspace)
    {
        $this->checkUserIsAllowed('agenda', $workspace);
        $form = $this->formFactory->create(FormFactory::TYPE_AGENDA);
        $form->handleRequest($this->request);
        if ($form->isValid()) {
            $event = $form->getData();
            // the end date has to be bigger
            if ($event->getStart() <= $event->getEnd()) {
                $event->setWorkspace($workspace);
                $event->setUser($this->security->getToken()->getUser());
                $this->om->persist($event);
                if ($event->getRecurring() > 0) {
                    $this->calculRecurrency($event);
                }
                $this->om->flush();
                $start = is_null($event->getStart())? null : $event->getStart()->getTimestamp();
                $end = is_null($event->getEnd())? null : $event->getEnd()->getTimestamp();
                $data = array(
                    'id' => $event->getId(),
                    'title' => $event->getTitle(),
                    'start' => $start,
                    'end' => $end,
                    'color' => $event->getPriority(),
                    'allDay' => $event->getAllDay()
                );

                return new Response(
                    json_encode($data),
                    200,
                    array('Content-Type' => 'application/json')
                );
            } else {
                return new Response(
                    json_encode(array('greeting' => ' start date is bigger than end date ')),
                    400,
                    array('Content-Type' => 'application/json')
                );
            }

            return new Response(
                json_encode(array('greeting' => 'dates are not valid')),
                400,
                array('Content-Type' => 'application/json')
            );
        }

        return new Response('Invalid data', 422);
    }

    /**
     * @EXT\Route(
     *     "/{workspaceId}/update",
     *     name="claro_workspace_agenda_update"
     * )
     * @EXT\Method("POST")
     * @EXT\ParamConverter(
     *      "workspace",
     *      class="ClarolineCoreBundle:Workspace\AbstractWorkspace",
     *      options={"id" = "workspaceId", "strictId" = true}
     * )
     *
     * @param AbstractWorkspace $workspace
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateAction(AbstractWorkspace $workspace)
    {
        $this->checkUserIsAllowed('agenda', $workspace);
        $postData = $this->request->request->all();
        $event = $this->om->getRepository('ClarolineCoreBundle:Event')->find($postData['id']);
        $form = $this->formFactory->create(FormFactory::TYPE_AGENDA, array(), $event);
        $form->handleRequest($this->request);
        if ($form->isValid()) {
            if (!$this->checkUserIsAllowedtoWrite($workspace, $event)) {
                throw new AccessDeniedException();
            }
            $event->setAllDay($postData['agenda_form']['allDay']);
            $this->om->flush();

            return new Response('', 204);
        }

        return new Response(
            json_encode(
                array('dates are not valids')
            ),
            400,
            array('Content-Type' => 'application/json')
        );
    }

    /**
     * @EXT\Route(
     *     "/{workspaceId}/delete",
     *     name="claro_workspace_agenda_delete"
     * )
     * @EXT\Method("POST")
     * @EXT\ParamConverter(
     *      "workspace",
     *      class="ClarolineCoreBundle:Workspace\AbstractWorkspace",
     *      options={"id" = "workspaceId", "strictId" = true}
     * )
     *
     * @param AbstractWorkspace $workspace
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(AbstractWorkspace $workspace)
    {

        $this->checkUserIsAllowed('agenda', $workspace);
        $repository = $this->om->getRepository('ClarolineCoreBundle:Event');
        $postData = $this->request->request->all();
        $event = $repository->find($postData['id']);
        if (!$this->checkUserIsAllowedtoWrite($workspace, $event)) {
            throw new AccessDeniedException();
        }
        $this->om->remove($event);
        $this->om->flush();

        return new Response(
            json_encode(array('greeting' => 'delete')),
            200,
            array('Content-Type' => 'application/json')
        );
    }

    /**
     * @EXT\Route(
     *     "/{workspaceId}/show",
     *     name="claro_workspace_agenda_show"
     * )
     * @EXT\Method({"GET","POST"})
     * @EXT\ParamConverter(
     *      "workspace",
     *      class="ClarolineCoreBundle:Workspace\AbstractWorkspace",
     *      options={"id" = "workspaceId", "strictId" = true}
     * )
     *
     * @param AbstractWorkspace $workspace
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction(AbstractWorkspace $workspace)
    {

        $this->checkUserIsAllowed('agenda', $workspace);
        $listEvents = $this->om->getRepository('ClarolineCoreBundle:Event')
            ->findbyWorkspaceId($workspace->getId(), false);
        $role = $this->checkUserIsAllowedtoWrite($workspace);
        $data = array();
        foreach ($listEvents as $key => $object) {
            $data[$key]['id'] = $object->getId();
            $data[$key]['title'] = $object->getTitle();
            $data[$key]['allDay'] = $object->getAllDay();
            $data[$key]['start'] = $object->getStart()->getTimestamp();
            $data[$key]['end'] = $object->getEnd()->getTimestamp();
            $data[$key]['color'] = $object->getPriority();
            $data[$key]['description'] = $object->getDescription();
            $data[$key]['owner'] = $object->getUser()->getUsername();
            if ($data[$key]['owner'] === $this->security->getToken()->getUser()->getUsername()) {
                $data[$key]['editable'] = true;
            } else {
                $data[$key]['editable'] = $role;
            }
        }

        return new Response(
            json_encode($data),
            200,
            array('Content-Type' => 'application/json')
        );
    }

    /**
     * @EXT\Route(
     *     "/move",
     *     name="claro_workspace_agenda_move"
     * )
     *  @param Event $event
     */
    public function moveAction()
    {
        $postData = $this->request->request->all();
        $repository = $this->om->getRepository('ClarolineCoreBundle:Event');
        $event = $repository->find($postData['id']);
        // if is null = desktop event
        if (!is_null($event->getWorkspace())) {
            $this->checkUserIsAllowed('agenda', $event->getWorkspace());

            if (!$this->checkUserIsAllowedtoWrite($event->getWorkspace())) {
                throw new AccessDeniedException();
            }
        }

        // timestamp 1h = 3600
        $newStartDate = strtotime(
            $postData['dayDelta'] . ' day ' . $postData['minuteDelta'] . ' minute',
            $event->getStart()->getTimestamp()
        );
        $dateStart = new \DateTime(date('d-m-Y H:i', $newStartDate));
        $event->setStart($dateStart);
        $newEndDate = strtotime(
            $postData['dayDelta'] . ' day ' . $postData['minuteDelta'] . ' minute',
            $event->getEnd()->getTimestamp()
        );
        $dateEnd = new \DateTime(date('d-m-Y H:i', $newEndDate));
        $event->setStart($dateStart);
        $event->setEnd($dateEnd);
        $this->om->flush();

        return new Response(
            json_encode(
                array(
                    'id' => $event->getId(),
                    'title' => $event->getTitle(),
                    'allDay' => $event->getAllDay(),
                    'start' => $event->getStart()->getTimestamp(),
                    'end' => $event->getEnd()->getTimestamp(),
                    'color' => $event->getPriority()
                    )
            ),
            200,
            array('Content-Type' => 'application/json')
        );
    }

    /**
     * @EXT\Route(
     *     "/{workspaceId}/tasks",
     *     name="claro_workspace_agenda_tasks"
     * )
     * @EXT\Method({"GET","POST"})
     * @EXT\ParamConverter(
     *      "workspace",
     *      class="ClarolineCoreBundle:Workspace\AbstractWorkspace",
     *      options={"id" = "workspaceId", "strictId" = true}
     * )
     * @param AbstractWorkspace $workspace
     *
     * @EXT\Template("ClarolineCoreBundle:Tool\\desktop\\agenda:tasks.html.twig")
     */
    public function tasksAction(AbstractWorkspace $workspaceId)
    {
        $listEvents = $this->om->getRepository('ClarolineCoreBundle:Event')->findByWorkspaceId($workspaceId, true);

        return  array('listEvents' => $listEvents );
    }

    /**
     * @EXT\Route(
     *     "/{workspaceId}/export",
     *     name="claro_workspace_agenda_export"
     * )
     * @EXT\Method({"GET","POST"})
     * @EXT\ParamConverter(
     *      "workspace",
     *      class="ClarolineCoreBundle:Workspace\AbstractWorkspace",
     *      options={"id" = "workspaceId", "strictId" = true}
     * )
     * @param AbstractWorkspace $workspace
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function exportsEventIcsAction(AbstractWorkspace $workspaceId)
    {
        $file =  $this->agendaManager->export($workspaceId);
        $response = new StreamedResponse();

        $response->setCallBack(
            function () use ($file) {
                readfile($file);
            }
        );
        $date = new \DateTime();
        $response->headers->set('Content-Transfer-Encoding', 'octet-stream');
        $response->headers->set('Content-Type', 'application/force-download');
        $response->headers->set('Content-Disposition', 'attachment; filename='.$workspaceId->getName().'.ics');
        $response->headers->set('Content-Type', ' text/calendar');
        $response->headers->set('Connection', 'close');

        return $response;
    }

    private function checkUserIsAllowed($permission, AbstractWorkspace $workspace)
    {
        if (!$this->security->isGranted($permission, $workspace)) {
            throw new AccessDeniedException();
        }
    }

    private function checkUserIsAllowedtoWrite(AbstractWorkspace $workspace, Event $event = null)
    {
        $usr = $this->security->getToken()->getUser();
        $rm = $this->rm->getManagerRole($workspace);
        $ru = $this->rm->getWorkspaceRolesForUser($usr, $workspace);
        
        if (!is_null($event)) {
            if ($event->getUser()->getUsername() === $usr->getUsername()) {
                return true;
            }
        }
        
        foreach ($ru as $role) {
            if ($role->getTranslationKey() === $rm->getTranslationKey()) {
                return true;
            }

            return false;
        }
    }

    private function calculRecurrency(Event $event)
    {
        $listEvents = array();

        // it calculs by day for now
        for ($i = 1; $i <= $event->getRecurring(); $i++) {
            $temp = clone $event;
            $newStartDate = $temp->getStart()->getTimestamp() + (3600 * 24 * $i);
            $temp->setStart(new \DateTime(date('d-m-Y H:i', $newStartDate)));
            $newEndDate = $temp->getEnd()->getTimestamp() + (3600 * 24 * $i);
            $temp->setEnd(new \DateTime(date('d-m-Y H:i', $newEndDate)));
            $listEvents[$i] = $temp;
            $this->om->persist($listEvents[$i]);

            return $listEvents;
        }
    }
}
