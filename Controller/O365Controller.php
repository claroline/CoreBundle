<?php

namespace Claroline\CoreBundle\Controller;

//require("C:/wamp/www/claroline/vendor/symfony/symfony/src/Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand.php");
//use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
//$filepath = realpath (dirname(__FILE__));
//require_once("C:/wamp/www/claroline/vendor/symfony/symfony/src/Symfony/Component/Security/Core/User/UserProviderInterface.php");
//require_once("C:/wamp/www/claroline/vendor/claroline/core-bundle/Claroline/CoreBundle/Repository/UserRepository.php");
//use Symfony\Component\Security\Core\User\UserInterface;
//use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

//use Claroline\CoreBundle\Manager\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use Claroline\CoreBundle\Library\Security\OfficeAuth\AuthorizationHelperForGraph;
use Claroline\CoreBundle\Library\Security\OfficeAuth\Settings;
use Claroline\CoreBundle\Library\Security\OfficeAuth\O365ResponseUser;
use Claroline\CoreBundle\Library\Security\OfficeAuth\GraphServiceAccessHelper;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Library\Security\PlatformRoles;

/**
 *  @author Nathan Brasseur <nbr@eonix.be>
 */
class O365Controller extends Controller 
{
    /**
     * @EXT\Route(
     *     "/token",
     *     name="claro_o365_get_token"
     * )
     *
     * @return Response
     */
    public function getTokenAction()
    {
        $url = AuthorizationHelperForGraph::getAuthorizatonURL();
        //header( );
        
        return new RedirectResponse($url);
    }
    
    /**
     * @EXT\Route(
     *     "/login",
     *     name="claro_o365_login"
     * )
     *
     * @return Response
     */
    public function loginAction()
    {
        AuthorizationHelperForGraph::GetAuthenticationHeaderFor3LeggedFlow($_GET['code']);
        $jsonResponse = GraphServiceAccessHelper::getMeEntry();
        $userResponse = new O365ResponseUser($jsonResponse);
        $userManager = $this->get('claroline.manager.user_manager');
        $email = $userResponse->getEmail();
        $user = $userManager->getUserByEmail($email);
        if ($user === null){
            $user = new User();
            $user->setFirstName($userResponse->getNickname());
            $user->setLastName($userResponse->getRealName());
            $user->setUsername($userResponse->getEmail());
            $user->setPlainPassword($userResponse->getEmail());
            $user->setMail($userResponse->getEmail());
            $roleName = PlatformRoles::USER;
            $userManager->createUser($user, $roleName);
        }

        $userRepo = $this->get('doctrine.orm.entity_manager')->getRepository('ClarolineCoreBundle:User');
        $securityContext = $this->get('security.context');
        $userLoaded = $userRepo->loadUserByUsername($user->getUsername());
        $providerKey = 'main';
        $token = new UsernamePasswordToken($userLoaded, $userLoaded->getPassword(), $providerKey, $userLoaded->getRoles());
        $securityContext->setToken($token);
        
        return new RedirectResponse($this->generateUrl('claro_desktop_open'));
    }
}
