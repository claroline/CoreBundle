<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Manager;

use Claroline\CoreBundle\Entity\Activity\ActivityParameters;
use Claroline\CoreBundle\Entity\Resource\Activity;
use Claroline\CoreBundle\Entity\Resource\ResourceNode;
use Claroline\CoreBundle\Manager\ResourceManager;
use Claroline\CoreBundle\Persistence\ObjectManager;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;
use JMS\DiExtraBundle\Annotation\Service;

/**
 * @Service("claroline.manager.activity_manager")
 */
class ActivityManager
{
    private $activityRuleRepo;
    private $evaluationRepo;
    private $persistence;
    private $resourceManager;

    /**
     * Constructor.
     *
     * @InjectParams({
     *     "persistence"        = @Inject("claroline.persistence.object_manager"),
     *     "resourceManager"    = @Inject("claroline.manager.resource_manager")
     * })
     */
    public function __construct(ObjectManager $persistence, ResourceManager $resourceManager)
    {
        $this->persistence = $persistence;
        $this->resourceManager = $resourceManager;
        $this->activityRuleRepo = $persistence->getRepository('ClarolineCoreBundle:Activity\ActivityRule');
        $this->evaluationRepo = $persistence->getRepository('ClarolineCoreBundle:Activity\Evaluation');
    }


    /**
     * Access to ActivityRuleRepository methods
     */
    public function getActivityRuleByActionAndResource(
        $action,
        ResourceNode $resourceNode,
        $executeQuery = true
    )
    {
        return $this->activityRuleRepo->findActivityRuleByActionAndResource(
            $action,
            $resourceNode->getId(),
            $executeQuery
        );
    }

    /**
     * Access to EvaluationRepository methods
     */
    public function getEvaluationByUserAndActivityParams(
        User $user,
        ActivityParameters $activityParams,
        $executeQuery = true
    )
    {
        return $this->evaluationRepo->findEvaluationByUserAndActivityParams(
            $user,
            $activityParams,
            $executeQuery
        );
    }

    /**
     * Create a new activity
     */
    public function createActivity($title, $description, $resourceNodeId, $persist = false)
    {
        $resourceNode = $this->resourceManager->getById($resourceNodeId);
        $parameters = new ActivityParameters();

        return $this->editActivity(new Activity(), $title, $description, $resourceNode, $parameters, $persist);
    }

    /**
     * Edit an activity
     */
    public function editActivity($activity, $title, $description, $resourceNode, $parameters, $persist = false)
    {
        $activity->setName($title);
        $activity->setTitle($title);
        $activity->setDescription($description);
        $activity->setResourceNode($resourceNode);
        $activity->setParameters($parameters);

        if ($persist) {
            $this->persistence->persist($activity);
            $this->persistence->flush();
        }

        return $activity;
    }

    /**
     * Delete an activity
     */
    public function deleteActivty($activity)
    {
        $this->persistence->remove($activity);
        $this->persistence->flush();
    }

    /**
     * Link a resource to an activity
     */
    public function addResource($resourceActivity)
    {
        $this->persistence->persist($resourceActivity);
        $this->persistence->flush();
    }

    /**
     * Edit a resource link in an activity
     */
    public function editResource($resourceActivity)
    {
        $this->persistence->persist($resourceActivity);
        $this->persistence->flush();
    }

    /**
     * delete a resource from an activity
     */
    public function deleteResource($resourceActivity)
    {
        $this->persistence->persist($resourceActivity);
        $this->persistence->flush();
    }
}