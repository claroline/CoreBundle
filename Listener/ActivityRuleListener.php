<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Listener;

use Claroline\CoreBundle\Event\LogCreateEvent;
use Claroline\CoreBundle\Manager\ActivityManager;
use Claroline\CoreBundle\Rule\Validator;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * @DI\Service
 */
class ActivityRuleListener
{
    private $activityManager;
    private $ruleValidator;
    private $hasSucceded = false;

    /**
     * @DI\InjectParams({
     *     "activityManager" = @DI\Inject("claroline.manager.activity_manager"),
     *     "ruleValidator"   = @DI\Inject("claroline.rule.validator")
     * })
     */
    public function __construct(
        ActivityManager $activityManager,
        Validator $ruleValidator
    )
    {
        $this->activityManager = $activityManager;
        $this->ruleValidator = $ruleValidator;
    }

    /**
     * @DI\Observe("claroline.log.create")
     *
     * @param \Claroline\CoreBundle\Event\LogCreateEvent $event
     */
    public function onLog(LogCreateEvent $event)
    {
        $log = $event->getLog();
        $dateLog = $log->getDateLog();
        $action = $log->getAction();
        $resourceNode = $log->getResourceNode();

        if (!is_null($resourceNode)) {
            $activityRules = $this->activityManager
                ->getActivityRuleByActionAndResource($action, $resourceNode);

            if (count($activityRules) > 0) {
                $user =  $log->getDoer();

                foreach ($activityRules as $activityRule) {
                    $activityParams = $activityRule->getActivityParameters();
//                    $activityNode = $activityParams->getActivity()->getResourceNode();
                    $accessFrom = $activityRule->getActiveFrom();
                    $accessUntil = $activityRule->getActiveUntil();

                    if ((is_null($accessFrom) || $dateLog >= $accessFrom)
                        && (is_null($accessUntil) || $dateLog <= $accessUntil)) {

                        $nbRules = is_null($activityParams->getRules()) ?
                            0 :
                            count($activityParams->getRules());

                        if (!is_null($user) && $nbRules > 0) {
                            $activityStatus = 'unknown';
                            $rulesLogs = $this->ruleValidator->validate(
                                $activityParams,
                                $user
                            );

                            if(isset($rulesLogs['validRules'])
                                && $rulesLogs['validRules'] >= $nbRules) {

                                $activityStatus = 'completed';
                                $this->hasSucceded = true;
                            }

                            $this->activityManager->manageEvaluation(
                                $user,
                                $activityParams,
                                $log,
                                $rulesLogs,
                                $activityStatus
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * @DI\Observe("kernel.response")
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if ($this->hasSucceded) {
            $content = $event->getResponse()->getContent();
            $content = str_replace('</body>', '<script>console.log("succeeded");</script></body>', $content);
            $event->getResponse()->setContent($content);
        }
    }
}
