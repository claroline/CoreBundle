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

use Claroline\CoreBundle\Entity\SecurityToken;
use Claroline\CoreBundle\Form\Administration as AdminForm;
use Claroline\CoreBundle\Library\Configuration\PlatformConfigurationHandler;
use Claroline\CoreBundle\Library\Configuration\UnwritableException;
use Claroline\CoreBundle\Library\Installation\Refresher;
use Claroline\CoreBundle\Library\Installation\Settings\MailingChecker;
use Claroline\CoreBundle\Library\Installation\Settings\MailingSettings;
use Claroline\CoreBundle\Library\Maintenance\MaintenanceHandler;
use Claroline\CoreBundle\Library\Session\DatabaseSessionValidator;
use Claroline\CoreBundle\Manager\CacheManager;
use Claroline\CoreBundle\Manager\ContentManager;
use Claroline\CoreBundle\Manager\IPWhiteListManager;
use Claroline\CoreBundle\Manager\LocaleManager;
use Claroline\CoreBundle\Manager\MailManager;
use Claroline\CoreBundle\Manager\RoleManager;
use Claroline\CoreBundle\Manager\SecurityTokenManager;
use Claroline\CoreBundle\Manager\TermsOfServiceManager;
use Claroline\CoreBundle\Manager\ToolManager;
use Claroline\CoreBundle\Manager\UserManager;
use Claroline\CoreBundle\Manager\WorkspaceManager;
use JMS\DiExtraBundle\Annotation as DI;
use JMS\SecurityExtraBundle\Annotation as SEC;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * @DI\Tag("security.secure_service")
 * @SEC\PreAuthorize("canOpenAdminTool('platform_parameters')")
 */
class ParametersController extends Controller
{
    private $configHandler;
    private $roleManager;
    private $formFactory;
    private $request;
    private $localeManager;
    private $translator;
    private $mailManager;
    private $contentManager;
    private $cacheManager;
    private $dbSessionValidator;
    private $refresher;
    private $hwiManager;
    private $router;
    private $tokenManager;
    private $ipwlm;
    private $userManager;
    private $workspaceManager;

    /**
     * @DI\InjectParams({
     *     "configHandler"      = @DI\Inject("claroline.config.platform_config_handler"),
     *     "roleManager"        = @DI\Inject("claroline.manager.role_manager"),
     *     "formFactory"        = @DI\Inject("form.factory"),
     *     "localeManager"      = @DI\Inject("claroline.common.locale_manager"),
     *     "request"            = @DI\Inject("request"),
     *     "translator"         = @DI\Inject("translator"),
     *     "termsOfService"     = @DI\Inject("claroline.common.terms_of_service_manager"),
     *     "mailManager"        = @DI\Inject("claroline.manager.mail_manager"),
     *     "cacheManager"       = @DI\Inject("claroline.manager.cache_manager"),
     *     "contentManager"     = @DI\Inject("claroline.manager.content_manager"),
     *     "sessionValidator"   = @DI\Inject("claroline.session.database_validator"),
     *     "refresher"          = @DI\Inject("claroline.installation.refresher"),
     *     "toolManager"        = @DI\Inject("claroline.manager.tool_manager"),
     *     "sc"                 = @DI\Inject("security.context"),
     *     "router"             = @DI\Inject("router"),
     *     "ipwlm"              = @DI\Inject("claroline.manager.ip_white_list_manager"),
     *     "tokenManager"       = @DI\Inject("claroline.manager.security_token_manager"),
     *     "userManager"        = @DI\Inject("claroline.manager.user_manager"),
     *     "workspaceManager"   = @DI\Inject("claroline.manager.workspace_manager")
     * })
     */
    public function __construct(
        PlatformConfigurationHandler $configHandler,
        RoleManager $roleManager,
        FormFactory $formFactory,
        LocaleManager $localeManager,
        Request $request,
        Translator $translator,
        TermsOfServiceManager $termsOfService,
        MailManager $mailManager,
        ContentManager $contentManager,
        CacheManager $cacheManager,
        DatabaseSessionValidator $sessionValidator,
        Refresher $refresher,
        ToolManager $toolManager,
        SecurityContextInterface $sc,
        RouterInterface $router,
        IPWhiteListManager $ipwlm,
        SecurityTokenManager $tokenManager,
        UserManager $userManager,
        WorkspaceManager $workspaceManager
    )
    {
        $this->configHandler      = $configHandler;
        $this->roleManager        = $roleManager;
        $this->formFactory        = $formFactory;
        $this->request            = $request;
        $this->termsOfService     = $termsOfService;
        $this->localeManager      = $localeManager;
        $this->translator         = $translator;
        $this->mailManager        = $mailManager;
        $this->contentManager     = $contentManager;
        $this->cacheManager       = $cacheManager;
        $this->dbSessionValidator = $sessionValidator;
        $this->refresher          = $refresher;
        $this->sc                 = $sc;
        $this->toolManager        = $toolManager;
        $this->paramAdminTool     = $this->toolManager->getAdminToolByName('platform_parameters');
        $this->router             = $router;
        $this->ipwlm              = $ipwlm;
        $this->tokenManager       = $tokenManager;
        $this->userManager        = $userManager;
        $this->workspaceManager   = $workspaceManager;
    }

    /**
     * @EXT\Route("/", name="claro_admin_parameters_index")
     * @EXT\Template
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        return array();
    }

    /**
     * @EXT\Route("/general", name="claro_admin_parameters_general")
     * @EXT\Template
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function generalFormAction(Request $request)
    {
        $descriptions = $this->contentManager->getTranslatedContent(array('type' => 'platformDescription'));
        $platformConfig = $this->configHandler->getPlatformConfig();
        $role = $this->roleManager->getRoleByName($platformConfig->getDefaultRole());
        $form = $this->formFactory->create(
            new AdminForm\GeneralType(
                $this->localeManager->getAvailableLocales(),
                $role, $descriptions,
                $this->translator->trans('date_form_format', array(), 'platform'),
                $this->localeManager->getUserLocale($request),
                $this->configHandler->getLockedParamaters()
            ),
            $platformConfig
        );

        if ($this->request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                try {
                    $portfolioUrlOptions = $request->get('portfolioUrlOptions', 0);
                    $this->configHandler->setParameters(
                        array(
                            'allow_self_registration' => $form['selfRegistration']->getData(),
                            'locale_language' => $form['localeLanguage']->getData(),
                            'name' => $form['name']->getData(),
                            'support_email' => $form['support_email']->getData(),
                            'default_role' => $form['defaultRole']->getData()->getName(),
                            'redirect_after_login' => $form['redirect_after_login']->getData(),
                            'form_captcha' => $form['formCaptcha']->getData(),
                            'platform_init_date' => $form['platform_init_date']->getData(),
                            'platform_limit_date' => $form['platform_limit_date']->getData(),
                            'account_duration' => $form['account_duration']->getData(),
                            'anonymous_public_profile' => $form['anonymous_public_profile']->getData(),
                            'portfolio_url' => $portfolioUrlOptions ? $form['portfolio_url']->getData() : null,
                            'is_notification_active' => $form['isNotificationActive']->getData(),
                            'max_storage_size' => $form['maxStorageSize']->getData(),
                            'max_upload_resources' => $form['maxUploadResources']->getData(),
                            'max_workspace_users' => $form['workspaceMaxUsers']->getData()
                        )
                    );

                    $content = $request->get('platform_parameters_form');

                    if (isset($content['description'])) {
                        $descriptionContent = $this->contentManager->getContent(array('type' => 'platformDescription'));
                        if ($descriptionContent) {
                            $this->contentManager->updateContent($descriptionContent, $content['description']);
                        } else {
                            $this->contentManager->createContent($content['description'], 'platformDescription');
                        }
                    }

                    $logo = $request->files->get('logo');

                    if ($logo) {
                        $this->get('claroline.common.logo_service')->createLogo($logo);
                    }

                    $this->addFlashMessage('general_parameters_updated_success');

                    return $this->redirect($this->generateUrl('claro_admin_index'));
                } catch (UnwritableException $e) {
                    $form->addError(
                        new FormError(
                            $this->translator->trans(
                                'unwritable_file_exception',
                                array('%path%' => $e->getPath()),
                                'platform'
                            )
                        )
                    );
                }
            }
        }

        return array(
            'form_settings' => $form->createView(),
            'logos' => $this->get('claroline.common.logo_service')->listLogos()
        );
    }

    /**
     * @EXT\Route("/appearance", name="claro_admin_parameters_appearance")
     * @EXT\Template
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function appearanceFormAction()
    {
        $platformConfig = $this->configHandler->getPlatformConfig();
        $form = $this->formFactory->create(
            new AdminForm\AppearanceType(
                $this->getThemes(),
                $this->configHandler->getLockedParamaters()
            ),
            $platformConfig
        );

        if ($this->request->isMethod('POST')) {
            $form->handleRequest($this->request);
            if ($form->isValid()) {
                try {
                    $this->configHandler->setParameters(
                        array(
                            'nameActive' => $form['name_active']->getData(),
                            'theme' => $form['theme']->getData(),
                            'footer' => $form['footer']->getData(),
                            'logo' => $this->request->get('selectlogo'),
                        )
                    );

                    $logo = $this->request->files->get('logo');

                    if ($logo) {
                        $this->get('claroline.common.logo_service')->createLogo($logo);
                    }

                    $this->addFlashMessage('parameters_save_success');

                    return $this->redirect($this->generateUrl('claro_admin_index'));
                } catch (UnwritableException $e) {
                    $form->addError(
                        new FormError(
                            $this->translator->trans(
                                'unwritable_file_exception',
                                array('%path%' => $e->getPath()),
                                'platform'
                            )
                        )
                    );
                }
            }
        }

        return array(
            'form_appearance' => $form->createView(),
            'logos' => $this->get('claroline.common.logo_service')->listLogos()
        );
    }

    /**
     * @EXT\Route("/mail", name="claro_admin_parameters_mail_index")
     * @EXT\Template
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function mailIndexAction()
    {
        return array();
    }

    /**
     * @EXT\Route("/mail/server", name="claro_admin_parameters_mail_server")
     * @EXT\Template
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function mailServerFormAction()
    {
        $platformConfig = $this->configHandler->getPlatformConfig();
        $form = $this->formFactory->create(
            new AdminForm\MailServerType(
                $platformConfig->getMailerTransport(),
                $this->configHandler->getLockedParamaters()
            ),
            $platformConfig
        );

        return array('form_mail' => $form->createView());
    }


    /**
     * @EXT\Route("/mail/server/submit", name="claro_admin_edit_parameters_mail_server")
     * @EXT\Method("POST")
     * @EXT\Template("ClarolineCoreBundle:Administration\Parameters:mailServerForm.html.twig")
     *
     * Updates the platform settings and redirects to the settings form.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function submitMailServerAction()
    {
        $platformConfig = $this->configHandler->getPlatformConfig();
        $form = $this->formFactory->create(
            new AdminForm\MailServerType(
                $platformConfig->getMailerTransport(),
                $this->configHandler->getLockedParamaters()
            ),
            $platformConfig
        );
        $form->handleRequest($this->request);

        $data = array(
            'transport' => $form['mailer_transport']->getData(),
            'host' => $form['mailer_host']->getData(),
            'username' => $form['mailer_username']->getData(),
            'password' => $form['mailer_password']->getData(),
            'auth_mode' => $form['mailer_auth_mode']->getData(),
            'encryption' => $form['mailer_encryption']->getData(),
            'port' => $form['mailer_port']->getData()
        );

        $settings = new MailingSettings();
        $settings->setTransport($data['transport']);
        $settings->setTransportOptions($data);
        $errors = $settings->validate();

        if (count($errors) > 0) {
            foreach ($errors as $field => $error) {
                $trans = $this->translator->trans($error, array(), 'platform');
                $form->get('mailer_' . $field)->addError(new FormError($trans));
            }

            return array('form_mail' => $form->createView());
        }

        $checker = new MailingChecker($settings);
        $error = $checker->testTransport();

        if ($error != 1) {
            $session = $this->request->getSession();
            $session->getFlashBag()->add('error', $this->translator->trans($error, array(), 'platform'));

            return array('form_mail' => $form->createView());
        }

        $this->configHandler->setParameters(
            array(
                'mailer_transport' => $data['transport'],
                'mailer_host' => $data['host'],
                'mailer_username' => $data['username'],
                'mailer_password' => $data['password'],
                'mailer_auth_mode' => $data['auth_mode'],
                'mailer_encryption' => $data['encryption'],
                'mailer_port' => $data['port']
            )
        );

        $this->cacheManager->setParameter('is_mailer_available', true);

        return $this->redirect($this->generateUrl('claro_admin_index'));
    }

    /**
     * @EXT\Route("/mail/server/reset", name="claro_admin_reset_mail_server")
     *
     * Reset the mail settings.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function resetMailServerAction()
    {
        $data = array(
            'mailer_transport'  => 'smtp',
            'mailer_host'       => null,
            'mailer_username'   => null,
            'mailer_password'   => null,
            'mailer_auth_mode'  => null,
            'mailer_encryption' => null,
            'mailer_port'       => null
        );

        $this->configHandler->setParameters($data);
        $this->cacheManager->setParameter('is_mailer_available', false);

        return $this->redirect($this->generateUrl('claro_admin_parameters_mail_server'));
    }

    /**
     * @EXT\Route("/mail/registration", name="claro_admin_mail_registration")
     * @EXT\Template
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function registrationMailFormAction()
    {
        $form = $this->formFactory->create(
            new AdminForm\MailInscriptionType(),
            $this->mailManager->getMailInscription()
        );

        return array('form' => $form->createView());
    }

    /**
     * @EXT\Route("/mail/registration/submit", name="claro_admin_edit_mail_registration")
     * @EXT\Method("POST")
     * @EXT\Template("ClarolineCoreBundle:Administration\Parameters:registrationMailForm.html.twig")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @todo add csfr protection
     */
    public function submitRegistrationMailAction()
    {
        $formData = $this->request->get('platform_parameters_form');
        $form = $this->formFactory->create(new AdminForm\MailInscriptionType(), $formData['content']);
        $errors = $this->mailManager->validateMailVariable($formData['content'], '%password%');

        return array(
            'form' => $this->updateMailContent($formData, $form, $errors, $this->mailManager->getMailInscription())
        );
    }

    /**
     * @EXT\Route("/mail/layout", name="claro_admin_mail_layout")
     * @EXT\Template
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function mailLayoutFormAction()
    {
        $form = $this->formFactory->create(
            new AdminForm\MailLayoutType(),
            $this->mailManager->getMailLayout()
        );

        return array('form' => $form->createView());
    }

    /**
     * @EXT\Route("/mail/layout/submit", name="claro_admin_edit_mail_layout")
     * @EXT\Method("POST")
     * @EXT\Template("ClarolineCoreBundle:Administration\Parameters:mailLayoutForm.html.twig")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @todo add csfr protection
     */
    public function submitMailLayoutAction()
    {
        $formData = $this->request->get('platform_parameters_form');
        $form = $this->formFactory->create(new AdminForm\MailLayoutType(), $formData['content']);
        $errors = $this->mailManager->validateMailVariable($formData['content'], '%content%');

        return array(
            'form' => $this->updateMailContent($formData, $form, $errors, $this->mailManager->getMailLayout())
        );
    }

    /**
     * @EXT\Route("/terms", name="claro_admin_edit_terms_of_service")
     * @EXT\Template
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function termsOfServiceFormAction()
    {
        $form = $this->formFactory->create(
            new AdminForm\TermsOfServiceType(
                $this->configHandler->getParameter('terms_of_service'),
                $this->configHandler->getLockedParamaters()
            ),
            $this->termsOfService->getTermsOfService(false)
        );

        return array('form' => $form->createView());
    }

    /**
     * @EXT\Route("/terms/submit", name="claro_admin_edit_terms_of_service_submit")
     * @EXT\Method("POST")
     * @EXT\Template("ClarolineCoreBundle:Administration\Parameters:termsOfServiceForm.html.twig")
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function submitTermsOfServiceAction()
    {
        $form = $this->formFactory->create(
            new AdminForm\TermsOfServiceType(
                $this->configHandler->getParameter('terms_of_service'),
                $this->configHandler->getLockedParamaters()
            ),
            $this->termsOfService->getTermsOfService(false)
        );

        $form->handleRequest($this->request);

        if ($form->isValid()) {
            $areTermsEnabled = $form->get('active')->getData();
            $terms = $this->request->get('terms_of_service_form')['termsOfService'];

            if ($areTermsEnabled && $this->termsOfService->areTermsEmpty($terms)) {
                $error = $this->translator->trans('terms_enabled_but_empty', array(), 'platform');
                $form->addError(new FormError($error));
            } else {
                $this->termsOfService->setTermsOfService($terms);
                $this->configHandler->setParameter('terms_of_service', $areTermsEnabled);
            }
        }

        return array('form' => $form->createView());
    }

    /**
     * @EXT\Route("/indexing", name="claro_admin_parameters_indexing")
     * @EXT\Template
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function indexingFormAction()
    {
        $form = $this->formFactory->create(
            new AdminForm\IndexingType($this->configHandler->getLockedParamaters()),
            $this->configHandler->getPlatformConfig()
        );

        if ($this->request->getMethod() === 'POST') {
            $form->handleRequest($this->request);

            if ($form->isValid()) {
                $this->configHandler->setParameter('google_meta_tag', $form['google_meta_tag']->getData());

                $this->addFlashMessage('parameters_save_success');

                return $this->redirect($this->generateUrl('claro_admin_index'));
            }
        }

        return array('form' => $form->createView());
    }

    /**
     * @EXT\Route("/session", name="claro_admin_session")
     * @EXT\Template
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function sessionFormAction()
    {
        $config = $this->configHandler->getPlatformConfig();
        $form = $this->formFactory->create(
            new AdminForm\SessionType(
                $config->getSessionStorageType(),
                $config,
                $this->configHandler->getLockedParamaters()
            )
        );

        return array('form' => $form->createView());
    }

    /**
     * @EXT\Route("/session/submit", name="claro_admin_session_submit")
     * @EXT\Method("POST")
     * @EXT\Template("ClarolineCoreBundle:Administration\Parameters:sessionForm.html.twig")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function submitSessionAction()
    {
        $formData = $this->request->request->get('platform_session_form', array());
        $storageType = isset($formData['session_storage_type']) ?
            $formData['session_storage_type'] :
            $this->configHandler->getParameter('session_storage_type');
        $form = $this->formFactory->create(
            new AdminForm\SessionType(
                $storageType,
                null,
                $this->configHandler->getLockedParamaters()
            ),
            $this->configHandler->getPlatformConfig()
        );
        $form->handleRequest($this->request);

        if ($form->isValid()) {
            $data = array(
                'session_storage_type' => $form['session_storage_type']->getData(),
                'session_db_table' => $form['session_db_table']->getData(),
                'session_db_id_col' => $form['session_db_id_col']->getData(),
                'session_db_data_col' => $form['session_db_data_col']->getData(),
                'session_db_time_col' => $form['session_db_time_col']->getData(),
                'session_db_dsn' => $form['session_db_dsn']->getData(),
                'session_db_user' => $form['session_db_user']->getData(),
                'session_db_password' => $form['session_db_password']->getData(),
                'cookie_lifetime' => $form['cookie_lifetime']->getData()
            );

            $errors = $this->dbSessionValidator->validate($data);

            if (count($errors) === 0) {
                $this->configHandler->setParameters($data);
            } else {
                foreach ($errors as $error) {
                    $msg = $this->translator->trans($error, array(), 'platform');
                    $form->addError(new FormError($msg));
                }
            }
        }

        return array('form' => $form->createView());
    }

    /**
     * @EXT\Route("/oauth", name="claro_admin_parameters_oauth_index")
     * @EXT\Template
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function oauthIndexAction()
    {
        return array();
    }

    /**
     * @EXT\Route("delete/logo/{file}", name="claro_admin_delete_logo", options = {"expose"=true})
     *
     * @param $file
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteLogoAction($file)
    {
        try {
            $this->get('claroline.common.logo_service')->deleteLogo($file);

            return new Response('true');
        } catch (\Exeption $e) {
            return new Response('false'); //useful in ajax
        }
    }

    /**
     * @EXT\Route("/maintenance", name="claro_admin_parameters_maintenance")
     * @EXT\Template("ClarolineCoreBundle:Administration\Parameters:maintenance.html.twig")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function maintenancePageAction()
    {
        //the current ip must be whitelisted so it can access the the plateform when it's under maintenance
        $this->ipwlm->addIP($_SERVER['REMOTE_ADDR']);

        return array();
    }

    /**
     * @EXT\Route("/maintenance/start", name="claro_admin_parameters_start_maintenance")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function startMaintenanceAction()
    {
        MaintenanceHandler::enableMaintenance();

        return new RedirectResponse($this->router->generate('claro_admin_parameters_index'));
    }

    /**
     * @EXT\Route("/maintenance/end", name="claro_admin_parameters_end_maintenance")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function endMaintenanceAction()
    {
        MaintenanceHandler::disableMaintenance();

        return new RedirectResponse($this->router->generate('claro_admin_parameters_index'));
    }

    /**
     * @EXT\Route(
     *     "/security/token/order/{order}/direction/{direction}",
     *     name="claro_admin_security_token_list",
     *     defaults={"order"="clientName","direction"="ASC"},
     * )
     * @EXT\Template(
     *     "ClarolineCoreBundle:Administration\Parameters:securityTokenList.html.twig"
     * )
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function securityTokenListAction($order, $direction)
    {
        $tokens = $this->tokenManager->getAllTokens($order, $direction);

        return array(
            'tokens' => $tokens,
            'direction' => $direction
        );
    }

    /**
     * @EXT\Route(
     *     "/security/token/create/form",
     *     name="claro_admin_security_token_create_form"
     * )
     * @EXT\Template(
     *     "ClarolineCoreBundle:Administration\Parameters:securityTokenCreateForm.html.twig"
     * )
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function securityTokenCreateFormAction()
    {
        $form = $this->formFactory->create(
            new AdminForm\SecurityTokenType(),
            new SecurityToken()
        );

        return array('form' => $form->createView());
    }

    /**
     * @EXT\Route(
     *     "/security/token/create",
     *     name="claro_admin_security_token_create"
     * )
     * @EXT\Template(
     *     "ClarolineCoreBundle:Administration\Parameters:securityTokenCreateForm.html.twig"
     * )
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function securityTokenCreateAction()
    {
        $securityToken = new SecurityToken();
        $form = $this->formFactory->create(
            new AdminForm\SecurityTokenType(),
            $securityToken
        );
        $form->handleRequest($this->request);

        if ($form->isValid()) {
            $this->tokenManager->persistSecurityToken($securityToken);

            return new RedirectResponse(
                $this->router->generate('claro_admin_security_token_list')
            );
        }

        return array('form' => $form->createView());
    }

    /**
     * @EXT\Route(
     *     "/security/token/{tokenId}/edit/form",
     *     name="claro_admin_security_token_edit_form"
     * )
     * @EXT\ParamConverter(
     *     "securityToken",
     *     class="ClarolineCoreBundle:SecurityToken",
     *     options={"id" = "tokenId", "strictId" = true}
     * )
     * @EXT\Template(
     *     "ClarolineCoreBundle:Administration\Parameters:securityTokenEditForm.html.twig"
     * )
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function securityTokenEditFormAction(SecurityToken $securityToken)
    {
        $form = $this->formFactory->create(
            new AdminForm\SecurityTokenType(),
            $securityToken
        );

        return array(
            'form' => $form->createView(),
            'token' => $securityToken
        );
    }

    /**
     * @EXT\Route(
     *     "/security/token/{tokenId}/edit",
     *     name="claro_admin_security_token_edit"
     * )
     * @EXT\ParamConverter(
     *     "securityToken",
     *     class="ClarolineCoreBundle:SecurityToken",
     *     options={"id" = "tokenId", "strictId" = true}
     * )
     * @EXT\Template(
     *     "ClarolineCoreBundle:Administration\Parameters:securityTokenEditForm.html.twig"
     * )
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function securityTokenEditAction(SecurityToken $securityToken)
    {
        $form = $this->formFactory->create(
            new AdminForm\SecurityTokenType(),
            $securityToken
        );
        $form->handleRequest($this->request);

        if ($form->isValid()) {
            $this->tokenManager->persistSecurityToken($securityToken);

            return new RedirectResponse(
                $this->router->generate('claro_admin_security_token_list')
            );
        }

        return array(
            'form' => $form->createView(),
            'token' => $securityToken
        );
    }

    /**
     * @EXT\Route(
     *     "/security/token/{tokenId}/delete",
     *     name="claro_admin_security_token_delete",
     *     options={"expose"=true}
     * )
     * @EXT\ParamConverter(
     *     "securityToken",
     *     class="ClarolineCoreBundle:SecurityToken",
     *     options={"id" = "tokenId", "strictId" = true}
     * )
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function securityTokenDeleteAction(SecurityToken $securityToken)
    {
        $this->tokenManager->deleteSecurityToken($securityToken);

        return new RedirectResponse(
            $this->router->generate('claro_admin_security_token_list')
        );
    }

    /**
     * @EXT\Route(
     *     "/send/datas/confirmation/form",
     *     name="claro_admin_send_datas_confirm_form"
     * )
     * @EXT\Template(
     *     "ClarolineCoreBundle:Administration\Parameters:sendDatasConfirmationForm.html.twig"
     * )
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function sendDatasConfirmationFormAction()
    {
        return array();
    }

    /**
     * @EXT\Route(
     *     "/send/datas/confirm",
     *     name="claro_admin_send_datas_confirm"
     * )
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function sendDatasConfirmAction()
    {
        $ds = DIRECTORY_SEPARATOR;
        $platformOptionsFile = $this->container->getParameter('kernel.root_dir') .
            $ds . 'config' . $ds . 'platform_options.yml';

        if (is_null($this->configHandler->getParameter('token'))) {
            $token = $this->generateToken(20);
            $this->configHandler->setParameter('token', $token);
        }
        $this->sendDatas();

        $this->configHandler->setParameter('confirm_send_datas', 'OK');

        return new RedirectResponse(
            $this->router->generate('claro_admin_parameters_index')
        );
    }

    /**
     * @EXT\Route(
     *     "/send/datas/token/{token}",
     *     name="claro_admin_send_datas"
     * )
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function sendDatasAction($token)
    {
        if ($token === $this->configHandler->getParameter('token') &&
            $this->configHandler->getParameter('confirm_send_datas') === 'OK') {

            $this->sendDatas(2);

            return new Response('success', 200);
        } else {

            return new Response('Forbidden', 403);
        }
    }

    /**
     *  Returns the list of available themes.
     *
     *  @return array
     */
    private function getThemes()
    {
        $tmp = array();

        foreach ($this->get('claroline.common.theme_service')->getThemes() as $theme) {
            $tmp[str_replace(' ', '-', strtolower($theme->getName()))] = $theme->getName();
        }

        return $tmp;
    }

    private function updateMailContent($formData, $form, $errors, $content)
    {
        if (count($errors) > 0) {
            if (isset($errors['no_content'])) {
                $form->get('content')->addError(
                    new FormError($this->translator->trans($errors['no_content'], array(), 'validators'))
                );
            }

            foreach ($errors as $language => $errors) {
                if (isset($errors['content'])) {
                    foreach ($errors['content'] as $error) {
                        $msg = $this->translator->trans($error, array('%language%' => $language), 'platform');
                        $form->get('content')->addError(new FormError($msg));
                    }
                }
            }
        } else {
            $this->contentManager->updateContent($content, $formData['content']);
        }

        return $form->createView();
    }

    protected function addFlashMessage($message, $type = 'success')
    {
        $this->get('session')->getFlashBag()->add(
            $type,
            $this->translator->trans($message, array(), 'platform')
        );
    }

    private function sendDatas($mode = 1)
    {
        $url = $this->configHandler->getParameter('datas_sending_url');
        $ip = $_SERVER['REMOTE_ADDR'];
        $name = $this->configHandler->getParameter('name');
        $lang = $this->configHandler->getParameter('locale_language');
        $country = $this->configHandler->getParameter('country');
        $supportEmail = $this->configHandler->getParameter('support_email');
        $version = $this->getCoreBundleVersion();
        $nbNonPersonalWorkspaces = $this->workspaceManager->getNbNonPersonalWorkspaces();
        $nbPersonalWorkspaces = $this->workspaceManager->getNbPersonalWorkspaces();
        $nbUsers = $this->userManager->getCountAllEnabledUsers();
        $type = $mode;
        $token = $this->configHandler->getParameter('token');

        $currentUrl = $this->request->getHttpHost() .
            $this->request->getRequestUri();
        $currentUrl = preg_replace(
            '/\/admin\/parameters\/send\/datas\/(.)*$/',
            '',
            $currentUrl
        );
        $platformUrl = preg_replace(
            array('/app\.php(.)*$/', '/app_dev\.php(.)*$/'),
            'app.php',
            $currentUrl
        );

        $postDatas = "ip=$ip" .
            "&name=$name" .
            "&url=$platformUrl" .
            "&lang=$lang" .
            "&country=$country" .
            "&email=$supportEmail" .
            "&version=$version" .
            "&workspaces=$nbNonPersonalWorkspaces" .
            "&personal_workspaces=$nbPersonalWorkspaces" .
            "&users=$nbUsers" .
            "&stats_type=$type" .
            "&token=$token";

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postDatas);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_exec($curl);
    }

    private function generateToken($length)
    {
        $chars = array_merge(range(0,9), range('a', 'z'), range('A', 'Z'));
        $charsSize = count($chars);
        $token = uniqid();

        while (strlen($token) < $length) {
            $index = rand(0, $charsSize - 1);
            $token .= $chars[$index];
        }

        return $token;
    }

    private function getCoreBundleVersion()
    {
        $ds = DIRECTORY_SEPARATOR;
        $version = '-';
        $installedFile = $this->container->getParameter('kernel.root_dir') .
            $ds . '..' . $ds . 'vendor' . $ds . 'composer' . $ds . 'installed.json';
        $jsonString = file_get_contents($installedFile);
        $bundles = json_decode($jsonString, true);

        foreach ($bundles as $bundle) {

            if (isset($bundle['name']) && $bundle['name'] === 'claroline/core-bundle') {
                $version = $bundle['version'];
                break;
            }
        }

        return $version;
    }
}
