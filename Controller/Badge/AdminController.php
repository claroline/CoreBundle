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
use Claroline\CoreBundle\Entity\Badge\BadgeClaim;
use Claroline\CoreBundle\Entity\Badge\BadgeRule;
use Claroline\CoreBundle\Entity\Badge\BadgeTranslation;
use Claroline\CoreBundle\Entity\User;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Exception\NotValidCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use JMS\SecurityExtraBundle\Annotation as SEC;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * @DI\Tag("security.secure_service")
 * @SEC\PreAuthorize("hasRole('ADMIN')")
 *
 * Controller of the badges.
 *
 * @Route("/admin/badges")
 */
class AdminController extends Controller
{
    /**
     * @Route(
     *     "/{badgePage}/{claimPage}",
     *     name="claro_admin_badges",
     *     requirements={"badgePage" = "\d+", "claimPage" = "\d+"},
     *     defaults={"badgePage" = 1, "claimPage" = 1}
     * )
     *
     * @Template
     */
    public function listAction($badgePage, $claimPage)
    {
        /** @var \Claroline\CoreBundle\Library\Configuration\PlatformConfigurationHandler $platformConfigHandler */
        $platformConfigHandler = $this->get('claroline.config.platform_config_handler');

        $parameters = array(
            'badgePage'        => $badgePage,
            'claimPage'        => $claimPage,
            'add_link'         => 'claro_admin_badges_add',
            'edit_link'        => array(
                'url'    => 'claro_admin_badges_edit',
                'suffix' => '#!edit'
            ),
            'delete_link'      => 'claro_admin_badges_delete',
            'view_link'        => 'claro_admin_badges_edit',
            'current_link'     => 'claro_admin_badges',
            'claim_link'       => 'claro_admin_manage_claim',
            'route_parameters' => array()
        );

        return array(
            'parameters'  => $parameters,
            'language'    => $platformConfigHandler->getParameter('locale_language')
        );
    }

    /**
     * @Route("/add", name="claro_admin_badges_add")
     *
     * @Template()
     */
    public function addAction(Request $request)
    {
        $badge = new Badge();

        //@TODO Get locales from locale source (database etc...)
        $locales = array('fr', 'en');
        foreach ($locales as $locale) {
            $translation = new BadgeTranslation();
            $translation->setLocale($locale);
            $badge->addTranslation($translation);
        }

        $form = $this->createForm($this->get('claroline.form.badge'), $badge);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                /** @var \Symfony\Bundle\FrameworkBundle\Translation\Translator $translator */
                $translator = $this->get('translator');
                try {
                    /** @var \Doctrine\Common\Persistence\ObjectManager $entityManager */
                    $entityManager = $this->getDoctrine()->getManager();

                    $entityManager->persist($badge);
                    $entityManager->flush();

                    $this->get('session')
                        ->getFlashBag()
                        ->add('success', $translator->trans('badge_add_success_message', array(), 'badge'));
                } catch (\Exception $exception) {
                    $this->get('session')
                        ->getFlashBag()
                        ->add('error', $translator->trans('badge_add_error_message', array(), 'badge'));
                }

                return $this->redirect($this->generateUrl('claro_admin_badges'));
            }
        }

        return array(
            'form'  => $form->createView(),
            'badge' => $badge
        );
    }

    /**
     * @Route("/edit/{slug}/{page}", name="claro_admin_badges_edit")
     * @ParamConverter("badge", converter="badge_converter")
     *
     * @Template()
     */
    public function editAction(Request $request, Badge $badge, $page = 1)
    {
        /** @var \Claroline\CoreBundle\Library\Configuration\PlatformConfigurationHandler $platformConfigHandler */
        $platformConfigHandler = $this->get('claroline.config.platform_config_handler');
        $badge->setLocale($platformConfigHandler->getParameter('locale_language'));

        $doctrine = $this->getDoctrine();

        $query   = $doctrine->getRepository('ClarolineCoreBundle:Badge\Badge')->findUsers($badge, false);
        $adapter = new DoctrineORMAdapter($query);
        $pager   = new Pagerfanta($adapter);

        try {
            $pager->setCurrentPage($page);
        } catch (NotValidCurrentPageException $exception) {
            throw $this->createNotFoundException();
        }

        /** @var BadgeRule[] $originalRules */
        $originalRules = array();

        foreach ($badge->getRules() as $rule) {
            $originalRules[] = $rule;
        }

        $form = $this->createForm($this->get('claroline.form.badge'), $badge);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                /** @var \Symfony\Bundle\FrameworkBundle\Translation\Translator $translator */
                $translator = $this->get('translator');
                try {
                    /** @var \Doctrine\Common\Persistence\ObjectManager $entityManager */
                    $entityManager = $doctrine->getManager();

                    // Compute which rules was deleted
                    foreach ($badge->getRules() as $rule) {
                        foreach ($originalRules as $key => $originalRule) {
                            if ($originalRule->getId() === $rule->getId()) {
                                unset($originalRules[$key]);
                            }
                        }
                    }

                    // Delete rules
                    foreach ($originalRules as $rule) {
                        $entityManager->remove($rule);
                    }

                    $entityManager->persist($badge);
                    $entityManager->flush();

                    $this->get('session')
                        ->getFlashBag()
                        ->add('success', $translator->trans('badge_edit_success_message', array(), 'badge'));
                } catch (\Exception $exception) {
                    $this->get('session')
                        ->getFlashBag()
                        ->add('error', $translator->trans('badge_edit_error_message', array(), 'badge'));
                }

                return $this->redirect($this->generateUrl('claro_admin_badges'));
            }
        }

        return array(
            'form'  => $form->createView(),
            'badge' => $badge,
            'pager' => $pager
        );
    }

    /**
     * @Route("/delete/{slug}", name="claro_admin_badges_delete")
     * @ParamConverter("badge", converter="badge_converter")
     *
     * @Template()
     */
    public function deleteAction(Badge $badge)
    {
        if (null !== $badge->getWorkspace()) {
            throw $this->createNotFoundException("No badge found.");
        }

        /** @var \Symfony\Bundle\FrameworkBundle\Translation\Translator $translator */
        $translator = $this->get('translator');
        try {
            /** @var \Doctrine\Common\Persistence\ObjectManager $entityManager */
            $entityManager = $this->getDoctrine()->getManager();

            $entityManager->remove($badge);
            $entityManager->flush();

            $this->get('session')
                ->getFlashBag()
                ->add('success', $translator->trans('badge_delete_success_message', array(), 'badge'));
        } catch (\Exception $exception) {
            $this->get('session')
                ->getFlashBag()
                ->add('error', $translator->trans('badge_delete_error_message', array(), 'badge'));
        }

        return $this->redirect($this->generateUrl('claro_admin_badges'));
    }

    /**
     * @Route("/award/{slug}", name="claro_admin_badges_award")
     * @ParamConverter("badge", converter="badge_converter")
     *
     * @Template()
     */
    public function awardAction(Request $request, Badge $badge)
    {
        if (null !== $badge->getWorkspace()) {
            throw $this->createNotFoundException("No badge found.");
        }

        /** @var \Claroline\CoreBundle\Library\Configuration\PlatformConfigurationHandler $platformConfigHandler */
        $platformConfigHandler = $this->get('claroline.config.platform_config_handler');
        $badge->setLocale($platformConfigHandler->getParameter('locale_language'));

        $form = $this->createForm($this->get('claroline.form.badge.award'));

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                /** @var \Symfony\Bundle\FrameworkBundle\Translation\Translator $translator */
                $translator = $this->get('translator');
                try {
                    $doctrine = $this->getDoctrine();

                    $group        = $form->get('group')->getData();
                    $user         = $form->get('user')->getData();

                    /** @var \Claroline\CoreBundle\Entity\User[] $users */
                    $users = array();

                    if (null !== $group) {
                        $users = $doctrine->getRepository('ClarolineCoreBundle:User')->findByGroup($group);
                    } elseif (null !== $user) {
                        $users[] = $user;
                    }

                    /** @var \Claroline\CoreBundle\Manager\BadgeManager $badgeManager */
                    $badgeManager = $this->get('claroline.manager.badge');
                    $awardedBadge = $badgeManager->addBadgeToUsers($badge, $users);

                    $flashMessageType = 'error';

                    if (0 < $awardedBadge) {
                        $flashMessageType = 'success';
                    }

                    $message = $translator->transChoice(
                        'badge_awarded_count_message',
                        $awardedBadge,
                        array('%awaredBadge%' => $awardedBadge),
                        'badge'
                    );
                    $this->get('session')->getFlashBag()->add($flashMessageType, $message);
                } catch (\Exception $exception) {
                    if (!$request->isXmlHttpRequest()) {
                        $this->get('session')
                            ->getFlashBag()
                            ->add('error', $translator->trans('badge_award_error_message', array(), 'badge'));
                    } else {
                        return new Response($exception->getMessage(), 500);
                    }
                }

                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(array('error' => false));
                }

                return $this->redirect($this->generateUrl('claro_admin_badges_edit', array('slug' => $badge->getSlug())));
            }
        }

        return array(
            'badge'  => $badge,
            'form'   => $form->createView()
        );
    }

    /**
     * @Route("/unaward/{slug}/{username}", name="claro_admin_badges_unaward")
     * @ParamConverter("user", options={"mapping": {"username": "username"}})
     * @ParamConverter("badge", converter="badge_converter")
     *
     * @Template()
     */
    public function unawardAction(Request $request, Badge $badge, User $user)
    {
        if (null !== $badge->getWorkspace()) {
            throw $this->createNotFoundException("No badge found.");
        }

        /** @var \Claroline\CoreBundle\Library\Configuration\PlatformConfigurationHandler $platformConfigHandler */
        $platformConfigHandler = $this->get('claroline.config.platform_config_handler');
        $badge->setLocale($platformConfigHandler->getParameter('locale_language'));

        /** @var \Symfony\Bundle\FrameworkBundle\Translation\Translator $translator */
        $translator = $this->get('translator');
        try {
            $doctrine = $this->getDoctrine();
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $doctrine->getManager();

            $userBadge = $doctrine->getRepository('ClarolineCoreBundle:Badge\UserBadge')
                ->findOneByBadgeAndUser($badge, $user);

            $entityManager->remove($userBadge);
            $entityManager->flush();

            $this->get('session')
                ->getFlashBag()
                ->add('success', $translator->trans('badge_unaward_success_message', array(), 'badge'));
        } catch (\Exception $exception) {
            if (!$request->isXmlHttpRequest()) {
                $this->get('session')
                    ->getFlashBag()
                    ->add('error', $translator->trans('badge_unaward_error_message', array(), 'badge'));
            } else {
                return new Response($exception->getMessage(), 500);
            }
        }

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(array('error' => false));
        }

        return $this->redirect($this->generateUrl('claro_admin_badges_edit', array('slug' => $badge->getSlug())));
    }

    /**
     * @Route("/claim/manage/{id}/{validate}", name="claro_admin_manage_claim")
     *
     * @Template()
     */
    public function manageClaimAction(BadgeClaim $badgeClaim, $validate = false)
    {
        if (null !== $badgeClaim->getBadge()->getWorkspace()) {
            throw $this->createNotFoundException("No badge found.");
        }

        /** @var \Symfony\Bundle\FrameworkBundle\Translation\Translator $translator */
        $translator     = $this->get('translator');
        $successMessage = $translator->trans('badge_reject_award_success_message', array(), 'badge');
        $errorMessage   = $translator->trans('badge_reject_award_error_message', array(), 'badge');

        try {
            if ($validate) {
                $successMessage = $translator->trans('badge_validate_award_success_message', array(), 'badge');
                $errorMessage   = $translator->trans('badge_validate_award_error_message', array(), 'badge');

                /** @var \Claroline\CoreBundle\Manager\BadgeManager $badgeManager */
                $badgeManager = $this->get('claroline.manager.badge');
                $awardedBadge = $badgeManager->addBadgeToUser($badgeClaim->getBadge(), $badgeClaim->getUser());
                if (!$awardedBadge) {
                    $successMessage = $translator->trans('badge_already_award_info_message', array(), 'badge');
                }
            }

            $entityManager = $this->getDoctrine()->getManager();

            $entityManager->remove($badgeClaim);
            $entityManager->flush();

            $this->get('session')->getFlashBag()->add('success', $successMessage);
        } catch (\Exception $exception) {
            $this->get('session')->getFlashBag()->add('error', $errorMessage);
        }

        return $this->redirect($this->generateUrl('claro_admin_badges'));
    }
}
