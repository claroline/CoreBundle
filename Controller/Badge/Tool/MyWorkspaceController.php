<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Controller\Badge\Tool;

use Claroline\CoreBundle\Entity\Badge\Badge;
use Claroline\CoreBundle\Entity\Badge\BadgeClaim;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Entity\Workspace\Workspace;
use Claroline\CoreBundle\Event\Badge\BadgeCreateValidationLinkEvent;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/workspace/{workspaceId}")
 */
class MyWorkspaceController extends Controller
{
    /**
     * @Route(
     *     "/my_badges/{badgePage}",
     *     name="claro_workspace_tool_my_badges",
     *     requirements={"badgePage" = "\d+"},
     *     defaults={"badgePage" = 1}
     * )
     * @ParamConverter("loggedUser", options={"authenticatedUser" = true})
     * @ParamConverter(
     *     "workspace",
     *     class="ClarolineCoreBundle:Workspace\Workspace",
     *     options={"id" = "workspaceId"}
     * )
     * @Template
     */
    public function listAction(Workspace $workspace, User $loggedUser, $badgePage)
    {
        $this->checkUserIsAllowed($workspace);

        return array(
            'workspace'  => $workspace,
            'user'       => $loggedUser,
            'badgePage'  => $badgePage
        );
    }

    /**
     * @Route("/my_badges/claim/{badge_id}", name="claro_workspace_tool_claim_badge")
     * @ParamConverter(
     *     "workspace",
     *     class="ClarolineCoreBundle:Workspace\Workspace",
     *     options={"id" = "workspaceId"}
     * )
     * @ParamConverter("user", options={"authenticatedUser" = true})
     * @ParamConverter("badge", class="ClarolineCoreBundle:Badge\Badge", options={"id" = "badge_id"})
     * @Template()
     */
    public function claimAction(Workspace $workspace, User $user, Badge $badge)
    {
        $badgeClaim = new BadgeClaim();
        $badgeClaim->setUser($user);

        try {
            $flashBag   = $this->get('session')->getFlashBag();
            $translator = $this->get('translator');

            /** @var \Claroline\CoreBundle\Manager\BadgeManager $badgeManager */
            $badgeManager = $this->get('claroline.manager.badge');
            $badgeManager->makeClaim($badge, $user);

            $flashBag->add('success', $translator->trans('badge_claim_success_message', array(), 'badge'));
        } catch (\Exception $exception) {
            $flashBag->add('error', $translator->trans($exception->getMessage(), array(), 'badge'));
        }

        return $this->redirect($this->generateUrl('claro_workspace_tool_my_badges', array('workspaceId' => $workspace->getId())));
    }

    /**
     * @Route("/my_badge/{slug}", name="claro_workspace_tool_view_my_badge")
     * @ParamConverter(
     *     "workspace",
     *     class="ClarolineCoreBundle:Workspace\Workspace",
     *     options={"id" = "workspaceId"}
     * )
     * @ParamConverter("user", options={"authenticatedUser" = true})
     * @ParamConverter("badge", converter="badge_converter", options={"check_deleted" = false})
     * @Template()
     */
    public function viewAction(Workspace $workspace, Badge $badge, User $user)
    {
        $this->checkUserIsAllowed($workspace);

        /** @var \Claroline\CoreBundle\Rule\Validator $badgeRuleValidator */
        $badgeRuleValidator = $this->get("claroline.rule.validator");
        $validatedRules       = $badgeRuleValidator->validate($badge, $user);
        $validateLogsLink   = array();

        if (0 < $validatedRules['validRules']) {
            foreach ($validatedRules['rules'] as $ruleIndex => $validatedRule) {
                foreach ($validatedRule['logs'] as $logIndex => $validateLog) {
                    $validatedRules['rules'][$ruleIndex]['logs'][$logIndex] = array(
                        'log' => $validateLog,
                        'url' => null
                    );

                    $validationLink = null;
                    $eventLogName   = sprintf('badge-%s-generate_validation_link', $validateLog->getAction());

                    $eventDispatcher = $this->get('event_dispatcher');
                    if ($eventDispatcher->hasListeners($eventLogName)) {
                        $event = $eventDispatcher->dispatch(
                            $eventLogName,
                            new BadgeCreateValidationLinkEvent($validateLog)
                        );

                        $validationLink = $event->getContent();

                        if (null !== $validationLink) {
                            $validatedRules['rules'][$ruleIndex]['logs'][$logIndex]['url'] = $event->getContent();
                        }
                    }
                }
            }
        }

        $userBadge = $this->getDoctrine()->getRepository('ClarolineCoreBundle:Badge\UserBadge')->findOneBy(array('badge' => $badge, 'user' => $user));

        if (null === $userBadge) {
            throw $this->createNotFoundException("User don't have this badge.");
        }

        return array(
            'workspace'    => $workspace,
            'userBadge'      => $userBadge,
            'badge'          => $badge,
            'validatedRules' => $validatedRules
        );
    }

    private function checkUserIsAllowed(Workspace $workspace)
    {
        if (!$this->get('security.context')->isGranted('my_badges', $workspace)) {
            throw new AccessDeniedException();
        }
    }
}
