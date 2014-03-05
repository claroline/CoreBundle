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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\HttpFoundation\Request;
use Claroline\CoreBundle\Entity\Event;
use Claroline\CoreBundle\Form\Factory\FormFactory;
use Claroline\CoreBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Manager\AgendaManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\Translation\Translator;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controller of the Agenda
 */
class DesktopAgendaController extends Controller
{
    private $security;
    private $formFactory;
    private $om;
    private $request;
    private $translator;
    private $agendaManager;

    /**
     * @DI\InjectParams({
     *     "security"           = @DI\Inject("security.context"),
     *     "formFactory"        = @DI\Inject("claroline.form.factory"),
     *     "om"                 = @DI\Inject("claroline.persistence.object_manager"),
     *     "request"            = @DI\Inject("request"),
     *     "translator"         = @DI\Inject("translator"),
     *     "agendaManager"      = @DI\Inject("claroline.manager.agenda_manager")
     * })
     */
    public function __construct(
        SecurityContextInterface $security,
        FormFactory $formFactory,
        ObjectManager $om,
        Request $request,
        Translator $translator,
        AgendaManager $agendaManager
    )
    {
        $this->security = $security;
        $this->formFactory = $formFactory;
        $this->om = $om;
        $this->request = $request;
        $this->translator = $translator;
        $this->agendaManager = $agendaManager;
    }
    /**
     * @Route(
     *     "/show/",
     *     name="claro_desktop_agenda_show"
     * )
     */
    public function desktopShowAction()
    {
        $usr = $this->get('security.context')->getToken()->getUser();
        $listEvents = $this->om->getRepository('ClarolineCoreBundle:Event')->findByUser($usr, 0);
        $desktopEvents = $this->om->getRepository('ClarolineCoreBundle:Event')->findDesktop($usr, 0);
        $data = array_merge($this->convertEventoArray($listEvents), $this->convertEventoArray($desktopEvents));

        return new Response(
            json_encode($data),
            200,
            array('Content-Type' => 'application/json')
        );
    }

    /**
     * @Route(
     *     "/add/",
     *     name="claro_desktop_agenda_add"
     * )
    */
    public function addEvent()
    {
        $event = new Event();
        $form = $this->formFactory->create(FormFactory::TYPE_AGENDA, array(), $event);
        $form->handleRequest($this->request);
        if ($form->isValid()) {
            // the end date has to be bigger
            if ($event->getStart() <= $event->getEnd()) {
                $event->setUser($this->security->getToken()->getUser());
                $this->om->persist($event);
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
        } else {
             return new Response(
                 json_encode(array('greeting' => 'dates are not valid')),
                 400,
                 array('Content-Type' => 'application/json')
             );
        }

        return new Response('Invalid data', 422);
    }

    /**
     * @Route(
     *     "/delete",
     *     name="claro_desktop_agenda_delete"
     * )
     * @EXT\Method("POST")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction()
    {
        $repository = $this->om->getRepository('ClarolineCoreBundle:Event');
        $postData = $this->request->request->all();
        $event = $repository->find($postData['id']);
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
     *     "/update",
     *     name="claro_desktop_agenda_update"
     * )
     * @EXT\Method("POST")
     *
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateAction()
    {
        $postData = $this->request->request->all();
        $event = $this->om->getRepository('ClarolineCoreBundle:Event')->find($postData['id']);
        $form = $this->formFactory->create(FormFactory::TYPE_AGENDA, array(), $event);
        $form->handleRequest($this->request);
        if ($form->isValid()) {
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
     *     "/tasks",
     *     name="claro_desktop_agenda_tasks"
     * )
     * @EXT\Method({"GET"})
     * @EXT\Template("ClarolineCoreBundle:Tool\\desktop\\agenda:tasks.html.twig")
     */
    public function tasksAction()
    {
        $usr = $this->get('security.context')->getToken()->getUser();
        $listEvents = $this->om->getRepository('ClarolineCoreBundle:Event')->findDesktop($usr, true);

        return  array('listEvents' => $listEvents );
    }

    /**
     * @EXT\Route(
     *     "/widget/{order}",
     *     name="claro_desktop_agenda_widget"
     * )
     * @EXT\Template("ClarolineCoreBundle:Widget:agenda_widget.html.twig")
     * @EXT\Method({"GET"})
     */
    public function widgetAction($order = null)
    {
        $em = $this-> get('doctrine.orm.entity_manager');
        $usr = $this->get('security.context')->getToken()->getUser();
        $listEventsDesktop = $em->getRepository('ClarolineCoreBundle:Event')->findDesktop($usr, false);
        $listEvents = $em->getRepository('ClarolineCoreBundle:Event')->findByUserWithoutAllDay($usr, 5, $order);

        return array('listEvents' => array_merge($listEvents, $listEventsDesktop));
    }

    /**
     * @EXT\Route(
     *     "/export",
     *     name="claro_desktop_agenda_export"
     * )
     * @EXT\Method({"GET"})
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function exportsEventIcsAction()
    {
        $file =  $this->agendaManager->export();
        $response = new StreamedResponse();

        $response->setCallBack(
            function () use ($file) {
                readfile($file);
            }
        );
        $date = new \DateTime();
        $response->headers->set('Content-Transfer-Encoding', 'octet-stream');
        $response->headers->set('Content-Type', 'application/force-download');
        $response->headers->set('Content-Disposition', 'attachment; filename= '.$date->getTimestamp().'desktop_events.ics');
        $response->headers->set('Content-Type', ' text/calendar');
        $response->headers->set('Connection', 'close');

        return $response;
    }

    private function convertEventoArray($listEvents)
    {
        $data = array();

        foreach ($listEvents as $key => $object) {
            $data[$key]['id'] = $object->getId();
            $workspace = $object->getWorkspace();
            $data[$key]['title'] = !is_null($workspace) ?
                $workspace->getName() :
                $this->translator->trans('desktop', array(), 'platform');
            $data[$key]['title'] .= ' : ' . $object->getTitle();
            $data[$key]['allDay'] = $object->getAllDay();
            $data[$key]['start'] = $object->getStart()->getTimestamp();
            $data[$key]['end'] = $object->getEnd()->getTimestamp();
            $data[$key]['color'] = $object->getPriority();
            $data[$key]['visible'] = true;
        }

        return($data);
    }

}
