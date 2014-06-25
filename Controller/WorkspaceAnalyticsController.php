<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Controller;

use Claroline\CoreBundle\Entity\Activity\ActivityParameters;
use Claroline\CoreBundle\Entity\Resource\Activity;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace;
use Claroline\CoreBundle\Library\Security\Utilities;
use Claroline\CoreBundle\Manager\ActivityManager;
use Claroline\CoreBundle\Manager\AnalyticsManager;
use Claroline\CoreBundle\Manager\ResourceManager;
use Claroline\CoreBundle\Manager\RoleManager;
use Claroline\CoreBundle\Manager\UserManager;
use JMS\DiExtraBundle\Annotation as DI;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\SecurityContextInterface;

class WorkspaceAnalyticsController extends Controller
{
    private $activityManager;
    private $analyticsManager;
    private $resourceManager;
    private $roleManager;
    private $securityContext;
    private $templating;
    private $userManager;
    private $utils;

    /**
     * @DI\InjectParams({
     *     "activityManager"  = @DI\Inject("claroline.manager.activity_manager"),
     *     "analyticsManager" = @DI\Inject("claroline.manager.analytics_manager"),
     *     "resourceManager"  = @DI\Inject("claroline.manager.resource_manager"),
     *     "roleManager"      = @DI\Inject("claroline.manager.role_manager"),
     *     "securityContext"  = @DI\Inject("security.context"),
     *     "templating"       = @DI\Inject("templating"),
     *     "userManager"      = @DI\Inject("claroline.manager.user_manager"),
     *     "utils"            = @DI\Inject("claroline.security.utilities")
     * })
     */
    public function __construct(
        ActivityManager $activityManager,
        AnalyticsManager $analyticsManager,
        ResourceManager $resourceManager,
        RoleManager $roleManager,
        SecurityContextInterface $securityContext,
        TwigEngine $templating,
        UserManager $userManager,
        Utilities $utils
    )
    {
        $this->activityManager = $activityManager;
        $this->analyticsManager = $analyticsManager;
        $this->resourceManager = $resourceManager;
        $this->roleManager = $roleManager;
        $this->securityContext = $securityContext;
        $this->templating = $templating;
        $this->userManager = $userManager;
        $this->utils = $utils;
    }

    /**
     * @EXT\Route(
     *     "/{workspaceId}/analytics/",
     *     name="claro_workspace_analytics_show"
     * )
     * @EXT\Method("GET")
     * @EXT\ParamConverter(
     *      "workspace",
     *      class="ClarolineCoreBundle:Workspace\AbstractWorkspace",
     *      options={"id" = "workspaceId", "strictId" = true}
     * )
     * @EXT\Template("ClarolineCoreBundle:Tool/workspace/analytics:analytics.html.twig")
     *
     * Displays workspace analytics home page
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function analyticsShowAction(AbstractWorkspace $workspace)
    {
        $datas = $this->container->get('claroline.manager.analytics_manager')
            ->getWorkspaceAnalytics($workspace);
        $datas['analyticsTab'] = 'analytics';

        return $datas;
    }

    /**
     * @EXT\Route(
     *     "/{workspaceId}/activities/evaluations",
     *     name="claro_workspace_activities_evaluations_show"
     * )
     * @EXT\Method("GET")
     * @EXT\ParamConverter("currentUser", options={"authenticatedUser" = true})
     * @EXT\ParamConverter(
     *      "workspace",
     *      class="ClarolineCoreBundle:Workspace\AbstractWorkspace",
     *      options={"id" = "workspaceId", "strictId" = true}
     * )
     *
     * Displays activities evaluations home tab of analytics tool
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function workspaceActivitiesEvaluationsShowAction(
        User $currentUser,
        AbstractWorkspace $workspace
    )
    {
        if (!$this->securityContext->isGranted('analytics', $workspace)) {

            throw new AccessDeniedException();
        }
        $roleNames = $currentUser->getRoles();
        $isWorkspaceManager = $this->isWorkspaceManager($workspace, $roleNames);

        if ($isWorkspaceManager) {
            $activities = $this->activityManager
                ->getActivityByWorkspace($workspace);

            // It only allows to prevent 1 DB request per activity when getting
            // resourceNode linked to activity
            $resourceType = $this->resourceManager->getResourceTypeByName('activity');
            $resourceNodes = $this->resourceManager
                ->getByWorkspaceAndResourceType($workspace, $resourceType);

            return new Response(
                $this->templating->render(
                    "ClarolineCoreBundle:Tool/workspace/analytics:workspaceManagerActivitiesEvaluations.html.twig",
                    array(
                        'analyticsTab' => 'activties_evaluations',
                        'workspace' => $workspace,
                        'activities' => $activities
                    )
                )
            );
        } else {
            $token = $this->securityContext->getToken();
            $userRoles = $this->utils->getRoles($token);

            $criteria = array();
            $criteria['roots'] = array();

            $root = $this->resourceManager->getWorkspaceRoot($workspace);
            $criteria['roots'][] = $root->getPath();

            $criteria['types'] = array('activity');
            $nodes = $this->resourceManager
                ->getByCriteria($criteria, $userRoles, true);
            $resourceNodeIds = array();

            foreach ($nodes as $node) {
                $resourceNodeIds[] = $node['id'];
            }
            $activities = $this->activityManager
                ->getActivitiesByResourceNodeIds($resourceNodeIds);

            $params = array();

            foreach ($activities as $activity) {
                $params[] = $activity->getParameters();
            }

            $evaluations =
                $this->activityManager->getEvaluationsByUserAndActivityParameters(
                    $currentUser,
                    $params
                );

            $evaluationsAssoc = array();

            foreach ($evaluations as $evaluation) {
                $resourceNodeId = $evaluation->getActivityParameters()->getActivity()->getId();
                $evaluationsAssoc[$resourceNodeId] = $evaluation;
            }

            return new Response(
                $this->templating->render(
                    "ClarolineCoreBundle:Tool/workspace/analytics:workspaceActivitiesEvaluations.html.twig",
                    array(
                        'analyticsTab' => 'activties_evaluations',
                        'workspace' => $workspace,
                        'activities' => $activities,
                        'evaluations' => $evaluationsAssoc
                    )
                )
            );
        }
    }

    /**
     * @EXT\Route(
     *     "/{workspaceId}/activity/parameters/{activityParametersId}/user/{userId}/past/evaluations/show/{displayType}",
     *     name="claro_workspace_activities_past_evaluations_show",
     *     options = {"expose": true}
     * )
     * @EXT\Method("GET")
     * @EXT\ParamConverter("currentUser", options={"authenticatedUser" = true})
     * @EXT\ParamConverter(
     *      "user",
     *      class="ClarolineCoreBundle:User",
     *      options={"id" = "userId", "strictId" = true}
     * )
     * @EXT\ParamConverter(
     *      "workspace",
     *      class="ClarolineCoreBundle:Workspace\AbstractWorkspace",
     *      options={"id" = "workspaceId", "strictId" = true}
     * )
     * @EXT\ParamConverter(
     *      "activityParameters",
     *      class="ClarolineCoreBundle:Activity\ActivityParameters",
     *      options={"id" = "activityParametersId", "strictId" = true}
     * )
     * @EXT\Template("ClarolineCoreBundle:Tool/workspace/analytics:workspaceActivitiesPastEvaluations.html.twig")
     *
     * Displays past evaluations of one activity for one user
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function workspaceActivitiesPastEvaluationsShowAction(
        User $currentUser,
        User $user,
        AbstractWorkspace $workspace,
        ActivityParameters $activityParameters,
        $displayType
    )
    {
        if (!$this->securityContext->isGranted('analytics', $workspace)) {

            throw new AccessDeniedException();
        }
        $roleNames = $currentUser->getRoles();
        $isWorkspaceManager = $this->isWorkspaceManager($workspace, $roleNames);

        if (!$isWorkspaceManager && ($currentUser->getId() !== $user->getId())) {

            throw new AccessDeniedException();
        }
        $activity = $activityParameters->getActivity();

        $pastEvals =
            $this->activityManager->getPastEvaluationsByUserAndActivityParams(
                $user,
                $activityParameters
            );

        return array(
            'user' => $user,
            'isWorkspaceManager' => $isWorkspaceManager,
            'activity' => $activity,
            'pastEvals' => $pastEvals,
            'displayType' => $displayType
        );
    }

    /**
     * @EXT\Route(
     *     "/workspace/manager/activity/{activityId}/evaluations",
     *     name="claro_workspace_manager_activity_evaluations_show"
     * )
     * @EXT\Method("GET")
     * @EXT\ParamConverter("currentUser", options={"authenticatedUser" = true})
     * @EXT\ParamConverter(
     *      "activity",
     *      class="ClarolineCoreBundle:Resource\Activity",
     *      options={"id" = "activityId", "strictId" = true}
     * )
     * @EXT\Template("ClarolineCoreBundle:Tool/workspace/analytics:workspaceManagerActivityEvaluations.html.twig")
     *
     * Displays evaluations of an activity for each user of the workspace
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function workspaceManagerActivityEvaluationsShowAction(
        User $currentUser,
        Activity $activity
    )
    {
        $roleNames = $currentUser->getRoles();
        $workspace = $activity->getResourceNode()->getWorkspace();
        $isWorkspaceManager = $this->isWorkspaceManager($workspace, $roleNames);

        if (!$isWorkspaceManager) {

            throw new AccessDeniedException();
        }
        $resourceNode = $activity->getResourceNode();
        $activityParams = $activity->getParameters();
        $roles = $this->roleManager
            ->getRolesWithRightsByResourceNode($resourceNode);
        $usersPager = $this->userManager->getByRolesIncludingGroups($roles);
        $users = array();

        foreach ($usersPager as $user) {
            $users[] = $user;
        }
        $allEvaluations = $this->activityManager
            ->getEvaluationsByUsersAndActivityParams($users, $activityParams);
        $evaluations = array();

        foreach ($allEvaluations as $evaluation) {
            $user = $evaluation->getUser();
            $evaluations[$user->getId()] = $evaluation;
        }

        return array(
            'analyticsTab' => 'activties_evaluations',
            'activity' => $activity,
            'activityParams' => $activityParams,
            'workspace' => $workspace,
            'users' => $usersPager,
            'evaluations' => $evaluations
        );
    }

    private function isWorkspaceManager(AbstractWorkspace $workspace, array $roleNames)
    {
        $isWorkspaceManager = false;
        $managerRole = 'ROLE_WS_MANAGER_' . $workspace->getGuid();

        if (in_array('ROLE_ADMIN', $roleNames) ||
            in_array($managerRole, $roleNames)) {

            $isWorkspaceManager = true;
        }

        return $isWorkspaceManager;
    }
}