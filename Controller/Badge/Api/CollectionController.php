<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Controller\Badge\Api;

use Claroline\CoreBundle\Entity\Badge\BadgeCollection;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Form\Badge\BadgeCollectionType;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @Route("/internalapi/badge_collection")
 */
class CollectionController extends Controller
{
    /**
     * @Route("/", name="claro_badge_collection_add", defaults={"_format" = "json"})
     * @Method({"POST"})
     * @ParamConverter("user", options={"authenticatedUser" = true})
     */
    public function addAction(Request $request, User $user)
    {
        $collection = new BadgeCollection();
        $collection->setUser($user);

        return $this->processForm($request, $collection, "POST");
    }

    /**
     * @Route("/{id}", name="claro_badge_collection_edit", defaults={"_format" = "json"})
     * @Method({"PATCH"})
     * @ParamConverter("user", options={"authenticatedUser" = true})
     */
    public function editAction(Request $request, User $user, BadgeCollection $collection)
    {
        return $this->processForm($request, $collection, "PATCH");
    }

    /**
     * @Route("/{id}", name="claro_badge_collection_delete", defaults={"_format" = "json"})
     * @Method({"DELETE"})
     * @ParamConverter("user", options={"authenticatedUser" = true})
     */
    public function deleteAction(Request $request, User $user, BadgeCollection $collection)
    {
        if ($collection->getUser() !== $user) {
            throw new AccessDeniedHttpException();
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($collection);
        $entityManager->flush();

        $view = View::create();
        $view->setStatusCode(204);
        return $this->get("fos_rest.view_handler")->handle($view);
    }

    private function processForm(Request $request, BadgeCollection $collection, $method = "PUT")
    {
        $form = $this->createForm($this->get("claroline.form.badge.collection"), $collection, array("method" => $method));

        $formParameters = $request->request->get($form->getName());

        // Patch for boolean value, parameters are only string and always true for boolean value
        if (isset($formParameters['is_shared'])) {
            if ('0' === $formParameters['is_shared']) {
                $formParameters['is_shared'] = false;
            }
        }

        $form->submit($formParameters, 'PATCH' !== $method);

        if ($form->isValid()) {
            if (!$collection->isIsShared()) {
                $collection->setSlug(null);
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($collection);
            $entityManager->flush();

            $view = View::create();
            $view->setStatusCode(201);

            $data = array(
                'collection' => array(
                    'id'        => $collection->getId(),
                    'name'      => $collection->getName(),
                    'is_shared' => $collection->isIsShared(),
                    'slug'      => $this->generateUrl("claro_badge_collection_share_view", array("slug" => $collection->getSlug()))
                )
            );

            $view->setData($data);

            return $this->get("fos_rest.view_handler")->handle($view);
        }

        $view = View::create($form, 400);
        return $this->get("fos_rest.view_handler")->handle($view);
    }
}
