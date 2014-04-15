<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Controller\Administration;

use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Event\StrictDispatcher;
use Claroline\CoreBundle\Form\Factory\FormFactory;
use Claroline\CoreBundle\Manager\LocaleManager;
use Claroline\CoreBundle\Manager\MailManager;
use Claroline\CoreBundle\Manager\RoleManager;
use Claroline\CoreBundle\Manager\UserManager;
use Claroline\CoreBundle\Manager\WorkspaceManager;
use JMS\DiExtraBundle\Annotation as DI;
use JMS\SecurityExtraBundle\Annotation as SEC;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

// Controller dependencies
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Innova\PathBundle\Manager\PathManager;
use Innova\PathBundle\Manager\PublishmentManager;
use Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace;
use Innova\PathBundle\Entity\Path\Path;
use Doctrine\ORM\EntityManager;

/**
 * @DI\Tag("security.secure_service")
 * @SEC\PreAuthorize("hasRole('ADMIN')")
 */
class UsersController extends Controller
{
    private $userManager;
    private $roleManager;
    private $eventDispatcher;
    private $formFactory;
    private $request;
    private $mailManager;
    private $workspaceManager;
    private $localeManager;
    private $router;

    protected $entityManager;
    protected $translator;

    /**
     * @DI\InjectParams({
     *     "userManager"        = @DI\Inject("claroline.manager.user_manager"),
     *     "workspaceManager"   = @DI\Inject("claroline.manager.workspace_manager"),
     *     "roleManager"        = @DI\Inject("claroline.manager.role_manager"),
     *     "eventDispatcher"    = @DI\Inject("claroline.event.event_dispatcher"),
     *     "formFactory"        = @DI\Inject("claroline.form.factory"),
     *     "request"            = @DI\Inject("request"),
     *     "mailManager"        = @DI\Inject("claroline.manager.mail_manager"),
     *     "localeManager"      = @DI\Inject("claroline.common.locale_manager"),
     *     "router"             = @DI\Inject("router"),
     *     "entityManager"      = @DI\Inject("doctrine.orm.entity_manager"),
     *     "translator"         = @DI\Inject("translator")
     * })
     */
    public function __construct(
        UserManager $userManager,
        RoleManager $roleManager,
        WorkspaceManager $workspaceManager,
        StrictDispatcher $eventDispatcher,
        FormFactory $formFactory,
        Request $request,
        MailManager $mailManager,
        LocaleManager $localeManager,
        RouterInterface $router,
        EntityManager $entityManager,
        $translator
    )
    {
        $this->userManager = $userManager;
        $this->roleManager = $roleManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->formFactory = $formFactory;
        $this->request = $request;
        $this->mailManager = $mailManager;
        $this->localeManager = $localeManager;
        $this->router = $router;
        $this->workspaceManager = $workspaceManager;

        $this->entityManager = $entityManager;
        $this->translator = $translator;
    }

    /**
     * @EXT\Route("/menu", name="claro_admin_users_management")
     * @EXT\Method("GET")
     * @EXT\Template
     *
     * @return Response
     */
    public function indexAction()
    {
        return array();
    }

    /**
     * @EXT\Route("/new", name="claro_admin_user_creation_form")
     * @EXT\Method("GET")
     * @EXT\ParamConverter("currentUser", options={"authenticatedUser" = true})
     * @EXT\Template
     *
     * Displays the user creation form.
     *
     * @param User $currentUser
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function userCreationFormAction(User $currentUser)
    {
        $roles = $this->roleManager->getPlatformRoles($currentUser);
        $form = $this->formFactory->create(
            FormFactory::TYPE_USER_FULL, array($roles, $this->localeManager->getAvailableLocales())
        );

        $error = null;

        if (!$this->mailManager->isMailerAvailable()) {
            $error = 'mail_not_available';
        }

        return array(
            'form_complete_user' => $form->createView(),
            'error' => $error
        );
    }

    /**
     * @EXT\Route("/new", name="claro_admin_create_user")
     * @EXT\Method("POST")
     * @EXT\ParamConverter("currentUser", options={"authenticatedUser" = true})
     * @EXT\Template("ClarolineCoreBundle:Administration/Users:userCreationForm.html.twig")
     *
     * Creates an user (and its personal workspace) and redirects to the user list.
     *
     * @param User $currentUser
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function createAction(User $currentUser)
    {

        $roles = $this->roleManager->getPlatformRoles($currentUser);
        $form = $this->formFactory->create(
            FormFactory::TYPE_USER_FULL, array($roles, $this->localeManager->getAvailableLocales())
        );
        $form->handleRequest($this->request);

        if ($form->isValid()) {

            $user = $form->getData();
            $newRoles = $form->get('platformRoles')->getData();
            $this->userManager->insertUserWithRoles($user, $newRoles);

            // 2 boutons de validation pour ce formulaire donc je teste le "Clic" sur le bouton "Enregistrez et ..." ERV.
            if ($this->getRequest()->request->get('submitAction') == 'enregistrer')
            {
                // Redirection vers la liste des utilisateurs.
                return $this->redirect($this->generateUrl('claro_admin_user_list'));
            }

            elseif ($this->getRequest()->request->get('submitAction') == 'modifier')
            {
                // Affichage du message "utilisateur(s) ajouté(s)"
                $this->get('session')->getFlashBag()->set(
                    'success',
                        $this->translator->trans("add_user_s_confirm_message", array(), "platform")
                );
                // Redirection vers le formulaire de création de l'utilisateur.
                return $this->redirect($this->generateUrl('claro_admin_create_user'));
            }
        }

        $error = null;

        if (!$this->mailManager->isMailerAvailable()) {
            $error = 'mail_not_available';
        }

        return array(
            'form_complete_user' => $form->createView(),
            'error' => $error
        );
    }

    /**
     * @EXT\Route(
     *     "/",
     *     name="claro_admin_multidelete_user",
     *     options = {"expose"=true}
     * )
     * @EXT\Method("DELETE")
     * @EXT\ParamConverter(
     *     "users",
     *      class="ClarolineCoreBundle:User",
     *      options={"multipleIds" = true}
     * )
     *
     * Removes many users from the platform.
     *
     * @param User[] $users
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(array $users)
    {
        foreach ($users as $user) {
            $this->userManager->deleteUser($user);
            $this->eventDispatcher->dispatch('log', 'Log\LogUserDelete', array($user));
        }

        return new Response('user(s) removed', 204);
    }

    /**
     * @EXT\Route(
     *     "/page/{page}/max/{max}/order/{order}/direction/{direction}",
     *     name="claro_admin_user_list",
     *     defaults={"page"=1, "search"="", "max"=50, "order"="id","direction"="ASC"},
     *     options = {"expose"=true}
     * )
     * @EXT\Method("GET")
     * @EXT\Route(
     *     "/users/page/{page}/search/{search}/max/{max}/order/{order}/direction/{direction}",
     *     name="claro_admin_user_list_search",
     *     defaults={"page"=1, "max"=50, "order"="id","direction"="ASC"},
     *     options = {"expose"=true}
     * )
     * @EXT\Template
     * @EXT\ParamConverter(
     *     "order",
     *     class="Claroline\CoreBundle\Entity\User",
     *     options={"orderable"=true}
     * )
     *
     * Displays the platform user list.
     *
     * @param integer $page
     * @param string  $search
     * @param integer $max
     * @param string  $order
     *
     * @return array
     */
    public function listAction($page, $search, $max, $order, $direction)
    {
        $pager = $search === '' ?
            $this->userManager->getAllUsers($page, $max, $order, $direction):
            $this->userManager->getUsersByName($search, $page, $max, $order, $direction);
        
        $direction = $direction === 'DESC' ? 'ASC' : 'DESC';

        return array('pager' => $pager, 'search' => $search, 'max' => $max, 'order' => $order, 'direction' => $direction);
    }

    /**
     * @EXT\Route(
     *     "/page/{page}/pic",
     *     name="claro_admin_user_list_pics",
     *     defaults={"page"=1, "search"=""},
     *     options = {"expose"=true}
     * )
     * @EXT\Method("GET")
     * @EXT\Route(
     *     "/page/{page}/pic/search/{search}",
     *     name="claro_admin_user_list_search_pics",
     *     defaults={"page"=1},
     *     options = {"expose"=true}
     * )
     * @EXT\Method("GET")
     * @EXT\Template
     *
     * Displays the platform user list.
     *
     * @param integer $page
     * @param string  $search
     *
     * @return array
     */
    public function listPicsAction($page, $search)
    {
        $pager = $search === '' ?
            $this->userManager->getAllUsers($page):
            $this->userManager->getUsersByName($search, $page);

        return array('pager' => $pager, 'search' => $search);
    }

    /**
     * @EXT\Route("/import", name="claro_admin_import_users_form")
     * @EXT\Method("GET")
     * @EXT\Template
     *
     * @return Response
     */
    public function importFormAction()
    {
        $form = $this->formFactory->create(FormFactory::TYPE_USER_IMPORT);

        return array('form' => $form->createView());
    }

    /**
     * @EXT\Route(
     *     "/user/{user}/workspaces/page/{page}/max/{max}",
     *     name="claro_admin_user_workspaces",
     *     defaults={"page"=1, "max"=50},
     *     options={"expose"=true}
     * )
     * @EXT\Method("GET")
     * @EXT\Template
     *
     * @param User    $user
     * @param integer $page
     * @param integer $max
     *
     * @return array
     */
    public function userWorkspaceListAction(User $user, $page, $max)
    {
        $pager = $this->workspaceManager->getOpenableWorkspacesByRolesPager($user->getRoles(), $page, $max);

        return array('user' => $user, 'pager' => $pager, 'page' => $page, 'max' => $max);
    }

    /**
     * @EXT\Route("/import", name="claro_admin_import_users")
     * @EXT\Method("POST")
     * @EXT\Template("ClarolineCoreBundle:Administration/Users:importForm.html.twig")
     *
     * @return Response
     */
    public function importAction()
    {
        $form = $this->formFactory->create(FormFactory::TYPE_USER_IMPORT);
        $form->handleRequest($this->request);

        if ($form->isValid()) {
            $file = $form->get('file')->getData();
            $lines = str_getcsv(file_get_contents($file), PHP_EOL);

            foreach ($lines as $line) {
                $users[] = str_getcsv($line, ';');
            }

            $this->userManager->importUsers($users);

            return new RedirectResponse($this->router->generate('claro_admin_user_list'));
        }

        return array('form' => $form->createView());
    }
}
