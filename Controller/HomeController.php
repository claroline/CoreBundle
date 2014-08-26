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

use Claroline\CoreBundle\Entity\Content;
use Claroline\CoreBundle\Entity\Home\Type;
use Claroline\CoreBundle\Manager\HomeManager;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @TODO doc
 */
class HomeController
{
    private $manager;
    private $request;
    private $security;
    private $templating;
    private $homeService;

    /**
     * @InjectParams({
     *     "manager"        = @Inject("claroline.manager.home_manager"),
     *     "security"       = @Inject("security.context"),
     *     "request"        = @Inject("request"),
     *     "templating"     = @Inject("templating"),
     *     "homeService"    = @Inject("claroline.common.home_service")
     * })
     */
    public function __construct(HomeManager $manager, Request $request, $security, $templating, $homeService)
    {
        $this->manager = $manager;
        $this->request = $request;
        $this->security = $security;
        $this->templating = $templating;
        $this->homeService = $homeService;
    }

    /**
     * Get content by id
     *
     * @Route(
     *     "/content/{content}/{type}/{father}",
     *     requirements={"content" = "\d+"},
     *     name="claroline_get_content_by_id_and_type",
     *     defaults={"type" = "home", "father" = null},
     *     options = {"expose" = true}
     * )
     *
     * @ParamConverter("content", class = "ClarolineCoreBundle:Content", options = {"id" = "content"})
     * @ParamConverter("father", class = "ClarolineCoreBundle:Content", options = {"id" = "father"})
     * @ParamConverter("type", class = "ClarolineCoreBundle:Home\Type", options = {"mapping" : {"type": "name"}})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function contentAction(Content $content, Type $type, Content $father = null)
    {
        return $this->render(
            'ClarolineCoreBundle:Home/types:'.(is_object($type) ? $type->getName() : 'home' ).'.html.twig',
            $this->manager->getContent($content, $type, $father),
            true
        );
    }

    /**
     * Render the home page of the platform
     *
     * @Route("/type/{type}", name="claro_get_content_by_type", options = {"expose" = true})
     * @Route("/", name="claro_index", defaults={"type" = "home"}, options = {"expose" = true})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function homeAction($type)
    {
        $response = $this->render(
            'ClarolineCoreBundle:Home:home.html.twig',
            array(
                'type' => $type,
                'region' => $this->renderRegions($this->manager->getRegionContents()),
                'content' => $this->typeAction($type)->getContent()
            )
        );
        $response->headers->addCacheControlDirective('no-cache', true);
        $response->headers->addCacheControlDirective('max-age', 0);
        $response->headers->addCacheControlDirective('must-revalidate', true);
        $response->headers->addCacheControlDirective('no-store', true);
        $response->headers->addCacheControlDirective('expires', '-1');

        return $response;
    }

    /**
     * Render the layout of contents by type.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function typeAction($type, $father = null, $region = null)
    {
        $layout = $this->manager->contentLayout($type, $father, $region);

        if ($layout) {
            return $this->render('ClarolineCoreBundle:Home:layout.html.twig', $this->renderContent($layout));
        }

        return $this->render('ClarolineCoreBundle:Home:error.html.twig', array('path' => $type));
    }

    /**
     * Render the page of types administration.
     *
     * @Route("/types", name="claroline_types_manager")
     * @Secure(roles="ROLE_ADMIN")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function typesAction()
    {
        $types = $this->manager->getTypes();

        $response = $this->render(
            'ClarolineCoreBundle:Home:home.html.twig',
            array(
                'type' => '_pages',
                'region' => $this->renderRegions($this->manager->getRegionContents()),
                'content' => $this->render(
                    'ClarolineCoreBundle:Home:types.html.twig',
                    array('types' => $types)
                )->getContent()
            )
        );
        $response->headers->addCacheControlDirective('no-cache', true);
        $response->headers->addCacheControlDirective('max-age', 0);
        $response->headers->addCacheControlDirective('must-revalidate', true);
        $response->headers->addCacheControlDirective('no-store', true);
        $response->headers->addCacheControlDirective('expires', '-1');

        return $response;
    }

    /**
     * Rename a content form
     *
     * @Route("/rename/type/{type}", name="claro_content_rename_type_form", options = {"expose" = true})
     * @Secure(roles="ROLE_ADMIN")
     *
     * @Template("ClarolineCoreBundle:Home:rename.html.twig")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renameContentFormAction($type)
    {
        return array('type' => $type);
    }

    /**
     * Rename a content form
     *
     * @Route("/rename/type/{type}/{name}", name="claro_content_rename_type", options = {"expose" = true})
     * @Secure(roles="ROLE_ADMIN")
     *
     * @ParamConverter("type", class = "ClarolineCoreBundle:home\Type", options = {"mapping" : {"type": "name"}})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renameContentAction($type, $name)
    {
        try {
            $this->manager->renameType($type, $name);

            return new Response('true');
        } catch (\Exeption $e) {
            return new Response('false'); //useful in ajax
        }
    }

    /**
     * Render the "move a content" form.
     *
     * @Route("/move/content/{currentType}", name="claroline_move_content_form", options = {"expose" = true})
     * @Secure(roles="ROLE_ADMIN")
     *
     * @Template("ClarolineCoreBundle:Home:move.html.twig")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function moveContentFormAction($currentType)
    {
        return array('currentType' => $currentType, 'pages' => $this->manager->getTypes());
    }

    /**
     * Render the "move a content" form.
     *
     * @Route("/move/content/{content}/{type}/{page}", name="claroline_move_content", options = {"expose" = true})
     *
     * @Secure(roles="ROLE_ADMIN")
     *
     * @Template("ClarolineCoreBundle:Home:move.html.twig")
     *
     * @ParamConverter("content", class = "ClarolineCoreBundle:Content", options = {"id" = "content"})
     * @ParamConverter("type", class = "ClarolineCoreBundle:home\Type", options = {"mapping" : {"type": "name"}})
     * @ParamConverter("page", class = "ClarolineCoreBundle:home\Type", options = {"mapping" : {"page": "name"}})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function moveContentAction($content, $type, $page)
    {
        try {
            $this->manager->moveContent($content, $type, $page);

            return new Response('true');
        } catch (\Exeption $e) {
            return new Response('false'); //useful in ajax
        }
     }

    /**
     * Render the page of the creator box.
     *
     * @Route("/content/creator/{type}/{id}/{father}", name="claroline_content_creator", defaults={"father" = null})
     *
     * @param string $type The type of the content to create.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function creatorAction($type, $id = null, $content = null, $father = null)
    {
        //cant use @Secure(roles="ROLE_ADMIN") annotation beacause this method is called in anonymous mode
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return $this->render(
                'ClarolineCoreBundle:Home/types:'.$type.'.creator.twig',
                $this->manager->getCreator($type, $id, $content, $father),
                true
            );
        }

        return new Response(); //return void and not an exeption
    }

    /**
     * Render the page of the menu.
     *
     * @param string $id   The id of the content.
     * @param string $size The size (content-12) of the content.
     * @param string $type The type of the content.
     *
     * @Template("ClarolineCoreBundle:Home:menu.html.twig")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function menuAction($id, $size, $type, $father = null, $region = null, $collapse = null)
    {
        return $this->manager->getMenu($id, $size, $type, $father, $region, $collapse);
    }

    /**
     * Render the HTML of the menu of sizes of the contents.
     *
     * @param string $id   The id of the content.
     * @param string $size The size (content-12) of the content.
     * @param string $type The type of the content.
     *
     * @Route("/content/size/{id}/{size}/{type}", name="claroline_content_size", options = {"expose" = true})
     *
     * @Template("ClarolineCoreBundle:Home:sizes.html.twig")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function sizeAction($id, $size, $type)
    {
        return array('id' => $id, 'size' => $size, 'type' => $type);
    }

    /**
     * Render the HTML of a content generated by an external url with Open Grap meta tags
     *
     * @Route("/content/graph", name="claroline_content_graph")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function graphAction()
    {
        $graph = $this->manager->getGraph($this->request->get('generated_content_url'));

        if (isset($graph['type'])) {
            return $this->render(
                'ClarolineCoreBundle:Home/graph:'.$graph['type'].'.html.twig',
                array('content' => $graph),
                true
            );
        }

        return new Response('false');
    }

    /**
     * Render the HTML of the regions.
     *
     * @Route("/content/region/{content}", name="claroline_content_region", options = {"expose" = true})
     *
     * @param string $content The id of the content or the entity object of a content.
     *
     * @ParamConverter("content", class = "ClarolineCoreBundle:Content", options = {"id" = "content"})
     *
     * @Template("ClarolineCoreBundle:Home:regions.html.twig")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function regionAction(Content $content)
    {
        return array('id' => $content->getId(), 'region' => $this->manager->getRegion($content));
    }

    /**
     * Create new content by POST method. This is used by ajax.
     * The response is the id of the new content in success, otherwise the response is the false word in a string.
     *
     * @Route(
     *     "/content/create/{type}/{father}",
     *     name="claroline_content_create",
     *     defaults={"type" = "home", "father" = null}
     * )
     *
     * @Secure(roles="ROLE_ADMIN")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createAction($type = null, $father = null)
    {
        if ($id = $this->manager->createContent($this->request->get('home_content_form'), $type, $father)) {
            return new Response($id);
        }

        return new Response('false'); //useful in ajax
    }

    /**
     * Update a content by POST method. This is used by ajax.
     * The response is the word true in a string in success, otherwise false.
     *
     * @Route(
     *     "/content/update/{content}/{size}/{type}",
     *     name="claroline_content_update",
     *     defaults={"size" = null, "type" = null}
     * )
     * @Secure(roles="ROLE_ADMIN")
     *
     * @ParamConverter("content", class = "ClarolineCoreBundle:Content", options = {"id" = "content"})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateAction($content, $size = null, $type = null)
    {
        try {
            $this->manager->UpdateContent($content, $this->request->get('home_content_form'), $size, $type);

            return new Response('true');
        } catch (\Exeption $e) {
            return new Response('false'); //useful in ajax
        }
    }

    /**
     * Reorder contents in types. This method is used by ajax.
     * The response is the word true in a string in success, otherwise false.
     *
     * @param string $type The type of the content.
     * @param string $a    The id of the content 1.
     * @param string $b    The id of the content 2.
     *
     * @Route("/content/reorder/{type}/{a}/{b}", requirements={"a" = "\d+"}, name="claroline_content_reorder")
     *
     * @Secure(roles="ROLE_ADMIN")
     *
     * @ParamConverter("type", class = "ClarolineCoreBundle:Home\Type", options = {"mapping": {"type": "name"}})
     *
     * @ParamConverter("a", class = "ClarolineCoreBundle:Content", options = {"id" = "a"})
     * @ParamConverter("b", class = "ClarolineCoreBundle:Content", options = {"id" = "b"})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function reorderAction($type, $a, Content $b = null)
    {
        try {
            $this->manager->reorderContent($type, $a, $b);

            return new Response('true');
        } catch (\Exeption $e) {
            return new Response('false'); //useful in ajax
        }
    }

    /**
     * Delete a content by POST method. This is used by ajax.
     * The response is the word true in a string in success, otherwise false.
     *
     * @Route("/content/delete/{content}", name="claroline_content_delete")
     * @Secure(roles="ROLE_ADMIN")
     *
     * @ParamConverter("content", class = "ClarolineCoreBundle:Content", options = {"id" = "content"})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($content)
    {
        try {
            $this->manager->deleteContent($content);

            return new Response('true');
        } catch (\Exeption $e) {
            return new Response('false'); //useful in ajax
        }
    }

    /**
     * Verify if a type exist.
     *
     * @Route("/content/typeexist/{name}", name="claroline_content_type_exist", options = {"expose" = true})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function typeExistAction($name)
    {
        if ($this->manager->typeExist($name)) {
            return new Response('true');
        }

        return new Response('false');
    }

    /**
     * Create a type by POST method. This is used by ajax.
     * The response is a template of the type in success, otherwise false.
     *
     * @Route("/content/createtype/{name}", name="claroline_content_createtype")
     * @Secure(roles="ROLE_ADMIN")
     *
     * @Template("ClarolineCoreBundle:Home:type.html.twig")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createTypeAction($name)
    {
        try {
            return array('type' => $this->manager->createType($name));
        } catch (\Exeption $e) {
            return new Response('false'); //useful in ajax
        }
    }

    /**
     * Delete a type by POST method. This is used by ajax.
     * The response is the word true in a string in success, otherwise false.
     *
     * @Route("/content/deletetype/{type}", name="claroline_content_deletetype")
     * @Secure(roles="ROLE_ADMIN")
     *
     * @ParamConverter("type", class = "ClarolineCoreBundle:Home\Type", options = {"id" = "type"})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deletetypeAction($type)
    {
        try {
            $this->manager->deleteType($type);

            return new Response('true');
        } catch (\Exeption $e) {
            return new Response('false'); //useful in ajax
        }
    }

    /**
     * Put a content into a region in front page as left, right, footer. This is sueful for menus.
     *
     * @Route("/region/{region}/{content}", requirements={"content" = "\d+"}, name="claroline_content_to_region")
     *
     * @ParamConverter("region", class = "ClarolineCoreBundle:Home\Region", options = {"mapping": {"region": "name"}})
     * @ParamConverter("content", class = "ClarolineCoreBundle:Content", options = {"id" = "content"})
     *
     * @Secure(roles="ROLE_ADMIN")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function contentToRegionAction($region, $content)
    {
        try {
            $this->manager->contentToRegion($region, $content);

            return new Response('true');
        } catch (\Exeption $e) {
            return new Response('false'); //useful in ajax
        }
    }

    /**
     * Update the collapse attribute of a content
     *
     * @Route(
     *     "/content/collapse/{content}/{type}",
     *     name="claroline_content_collapse",
     *     options = {"expose" = true}
     * )
     *
     * @Secure(roles="ROLE_ADMIN")
     *
     * @ParamConverter("content", class = "ClarolineCoreBundle:Content", options = {"id" = "content"})
     * @ParamConverter("type", class = "ClarolineCoreBundle:Home\Type", options = {"mapping" : {"type": "name"}})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function collapseAction($content, $type)
    {
        try {
            $this->manager->collapse($content, $type);

            return new Response('true');
        } catch (\Exeption $e) {
            return new Response('false');
        }
    }

    /**
     * Check if a string is a valid URL
     *
     * @Route("/cangeneratecontent", name="claroline_can_generate_content")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function canGenerateContentAction()
    {
        if ($this->manager->isValidUrl($this->request->get('url'))) {

            $graph = $this->manager->getGraph($this->request->get('url'));

            if (isset($graph['type'])) {
                return $this->render(
                    'ClarolineCoreBundle:Home/graph:'.$graph['type'].'.html.twig',
                    array('content' => $graph),
                    true
                );
            }
        }

        return new Response('false'); //in case is not valid URL
    }

    /**
     * Menu settings
     *
     * @Route("/content/menu/settings/{content}", name="claroline_content_menu_settings")
     * @Secure(roles="ROLE_ADMIN")
     *
     * @ParamConverter("content", class = "ClarolineCoreBundle:Content", options = {"id" = "content"})
     *
     * @Template("ClarolineCoreBundle:Home:menuSettings.html.twig")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function menuSettingsAction($content)
    {
        return array(
            'content' => $content,
            'menu' => $this->manager->getContentByType('menu', $content->getId()),
            'parameters' => $this->manager->getHomeParameters()
        );
    }

    /**
     * Save the menu settings
     *
     * @Route(
     *     "/content/menu/save/settings/{menu}/{login}/{workspaces}/{locale}",
     *     name="claroline_content_menu_save_settings",
     *     options = {"expose" = true}
     * )
     *
     * @param menu The id of the menu
     * @param login A Boolean that determine if there is the login button in the footer
     * @param workspaces A Boolean that determine if there is the workspace button in the footer
     * @param locale A boolean that determine if there is a locale button in the header
     *
     * @Secure(roles="ROLE_ADMIN")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function saveMenuSettingsAction($menu, $login, $workspaces, $locale)
    {
        try {
            $this->manager->saveHomeParameters($menu, $login, $workspaces, $locale);

            return new Response('true');
        } catch (\Exeption $e) {
            return new Response('false');
        }
    }

    /**
     * Render the HTML of the content.
     *
     * @return array
     */
    public function renderContent($layout)
    {
        $tmp = ' '; // void in case of not yet content

        if (isset($layout['content']) and isset($layout['type']) and is_array($layout['content'])) {
            foreach ($layout['content'] as $content) {
                $tmp .= $this->render(
                    'ClarolineCoreBundle:Home/types:'.$content['type'].'.html.twig', $content, true
                )->getContent();
            }
        }

        $layout['content'] = $tmp;

        return $layout;
    }

    /**
     * Render the HTML of the regions.
     *
     * @return string
     */
    public function renderRegions($regions)
    {
        $tmp = array();

        foreach ($regions as $name => $region) {
            $tmp[$name] = '';

            foreach ($region as $variables) {
                $tmp[$name] .= $this->render(
                    'ClarolineCoreBundle:Home/types:'.$variables['type'].'.html.twig', $variables, true
                )->getContent();
            }
        }

        return $tmp;
    }

    /**
     * Extends templating render
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function render($template, $variables, $default = false)
    {
        if ($default) {
            $template = $this->homeService->defaultTemplate($template);
        }

        return new Response($this->templating->render($template, $variables));
    }
}
