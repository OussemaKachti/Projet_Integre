<?php

namespace App\Security;

use App\Entity\User;
use App\Enum\RoleEnum;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator {
    use TargetPathTrait;
    
    public const LOGIN_ROUTE = 'app_login';
    
    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }
    
    public function authenticate(Request $request): Passport
    {
        $email = $request->getPayload()->getString('email');
        
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);
        
        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($request->getPayload()->getString('password')),
            [
                new CsrfTokenBadge('authenticate', $request->getPayload()->getString('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }
    
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Check if there's a stored target path (page the user was trying to access)
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }
        
        // Get the authenticated user
        $user = $token->getUser();
        
        // Check if the user is our User entity and is an administrator
        if ($user instanceof User) {
            // Check role using the User's hasRole method or by checking roles array
            $isAdmin = in_array('ROLE_ADMINISTRATEUR', $user->getRoles());
            
            if ($isAdmin) {
                // Redirect administrators to the admin dashboard
                return new RedirectResponse($this->urlGenerator->generate('app_admin_dashboard'));
            }
        }
        
        // Default redirect to home page for non-admin users
        return new RedirectResponse($this->urlGenerator->generate('app_home'));
    }
    
    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}