<?php

namespace Claroline\CoreBundle\Controller\Log;

use Claroline\CoreBundle\Event\Log\LogCreateDelegateViewEvent;
use Claroline\CoreBundle\Entity\Widget\WidgetInstance;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Translation\Translator;
use Claroline\CoreBundle\Form\Log\LogDesktopWidgetConfigType;
use Claroline\CoreBundle\Entity\Log\Log;
use Claroline\CoreBundle\Entity\Log\LogHiddenWorkspaceWidgetConfig;
use Claroline\CoreBundle\Form\Factory\FormFactory;
use Claroline\CoreBundle\Manager\ToolManager;
use Claroline\CoreBundle\Manager\WorkspaceManager;
use Claroline\CoreBundle\Entity\Log\LogWidgetConfig;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * Controller of the user profile.
 */
class LogController extends Controller
{
    private $toolManager;
    private $workspaceManager;
    private $eventDispatcher;
    private $security;
    private $formFactory;
    private $translator;

    /**
     * @DI\InjectParams({
     *     "toolManager"        = @DI\Inject("claroline.manager.tool_manager"),
     *     "workspaceManager"   = @DI\Inject("claroline.manager.workspace_manager"),
     *     "eventDispatcher"    = @DI\Inject("event_dispatcher"),
     *     "security"           = @DI\Inject("security.context"),
     *     "formFactory"        = @DI\Inject("claroline.form.factory"),
     *     "translator"         = @DI\Inject("translator")
     * })
     */
    public function __construct(
        ToolManager $toolManager,
        WorkspaceManager $workspaceManager,
        EventDispatcher $eventDispatcher,
        SecurityContextInterface $security,
        FormFactory $formFactory,
        Translator $translator
    )
    {
        $this->toolManager      = $toolManager;
        $this->workspaceManager = $workspaceManager;
        $this->eventDispatcher  = $eventDispatcher;
        $this->security         = $security;
        $this->formFactory      = $formFactory;
        $this->translator       = $translator;
    }

    /**
     * @EXT\Route(
     *     "/view_details/{logId}",
     *           name="claro_log_view_details",
     *           options={"expose"=true}
     * )
     * @EXT\ParamConverter(
     *      "log",
     *           class="ClarolineCoreBundle:Log\Log",
     *           options={"id" = "logId", "strictId" = true}
     * )
     *
     * Displays the public profile of an user.
     *
     * @param \Claroline\CoreBundle\Entity\Log\Log $log
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewDetailsAction(Log $log)
    {
        $eventLogName = 'create_log_details_' . $log->getAction();

        if ($this->eventDispatcher->hasListeners($eventLogName)) {
            $event = $this->eventDispatcher->dispatch(
                $eventLogName,
                new LogCreateDelegateViewEvent($log)
            );

            return new Response($event->getResponseContent());
        }

        return $this->render(
            'ClarolineCoreBundle:Log:view_details.html.twig',
            array('log' => $log)
        );
    }

    /**
     * @EXT\Route(
     *     "/update_workspace_widget_config/{widgetInstance}",
     *     name="claro_log_update_workspace_widget_config"
     * )
     * @EXT\Method("POST")
     */
    public function updateLogWorkspaceWidgetConfig(WidgetInstance $widgetInstance)
    {
        if (!$this->get('security.context')->isGranted('edit', $widgetInstance)) {
            throw new AccessDeniedException();
        }

        $config = $this->get('claroline.log.manager')->getLogConfig($widgetInstance);
        $form = $this->get('form.factory')->create($this->get('claroline.form.logWorkspaceWidgetConfig'), $config);

        $form->bind($this->getRequest());

        if ($form->isValid()) {
            if ($config) {
                $config->setAmount($form->get('amount')->getData());
                $config->setRestrictions($form->get('restrictions')->getData());
            } else {
                $config = new LogWidgetConfig();
                $config->setAmount($form->get('amount')->getData());
                $config->setRestrictions($form->get('restrictions')->getData());
                $config->setWidgetInstance($widgetInstance);
            }
        } else {
            return $this->render(
                'ClarolineCoreBundle:Log:config_workspace_widget_form.html.twig',
                array(
                    'form' => $form->createView(),
                    'instance' => $widgetInstance
                )
            );
        }

        $em = $this->get('doctrine.orm.entity_manager');
        $em->persist($config);
        $em->flush();

        return new Response('success', 204);
    }

    /**
     * @EXT\Route(
     *     "/update_desktop_widget_config/{widgetInstance}",
     *     name="claro_log_update_desktop_widget_config"
     * )
     * @EXT\Method("POST")
     */
    public function updateLogDesktopWidgetConfig(WidgetInstance $widgetInstance)
    {
        if (!$this->get('security.context')->isGranted('edit', $widgetInstance)) {
            throw new AccessDeniedException();
        }

        $em = $this->getDoctrine()->getManager();
        $config = $this->get('claroline.log.manager')->getLogConfig($widgetInstance);

        if ($widgetInstance->isAdmin()) {
            $user = null;
            $hiddenConfigs = array();
            $workspaces = array();
        } else {
            $user = $this->security->getToken()->getUser();
            $hiddenConfigs = $em->getRepository('ClarolineCoreBundle:Log\LogHiddenWorkspaceWidgetConfig')
                ->findBy(array('user' => $user));
            $workspaces = $this->workspaceManager
                ->getWorkspacesByUserAndRoleNames($user, array('ROLE_WS_COLLABORATOR', 'ROLE_WS_MANAGER'));
        }

        $form = $this->get('form.factory')->create(
            new LogDesktopWidgetConfigType(),
            null,
            array('workspaces' => $workspaces)
        );
        $form->bind($this->getRequest());

        if ($form->isValid()) {

            if (!$config) {
                $config = new LogWidgetConfig();
                $config->setWidgetInstance($widgetInstance);
            }

            $data = $form->getData();
            //remove all hiddenConfigs for user
            foreach ($hiddenConfigs as $hiddenConfig) {
                $em->remove($hiddenConfig);
            }
            $em->flush();

           foreach ($data as $workspaceId => $visible) {
                if ($workspaceId != 'amount' and $visible !== true) {
                    $hiddenConfig = new LogHiddenWorkspaceWidgetConfig();
                    $hiddenConfig->setUser($user);
                    $hiddenConfig->setWorkspaceId($workspaceId);
                    $em->persist($hiddenConfig);
                }
            }
            // save amount
            $config->setAmount($data['amount']);
            $em->persist($config);
            $em->flush();

        } else {
            return $this->render(
                'ClarolineCoreBundle:Log:config_desktop_widget_form.html.twig',
                array(
                    'form' => $form->createView(),
                    'instance' => $widgetInstance
                )
            );
        }

        return new Response('', 204);
    }
}
