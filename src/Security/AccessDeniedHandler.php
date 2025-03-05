<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Symfony\Component\Security\Core\Security;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    private $urlGenerator;
    private $security;

    public function __construct(UrlGeneratorInterface $urlGenerator, Security $security)
    {
        $this->urlGenerator = $urlGenerator;
        $this->security = $security;
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?Response
    {
        // If the request is AJAX, return a 403 JSON response
        if ($request->isXmlHttpRequest()) {
            return new Response(json_encode(['error' => 'Access Denied']), 403, [
                'Content-Type' => 'application/json'
            ]);
        }

        // If the user is logged in but doesn't have permission (trying to access admin, etc.)
        if ($this->security->getUser()) {
            return new RedirectResponse($this->urlGenerator->generate('access_denied'));
        }
        
        // If not logged in at all, redirect to login page with a return URL
        $targetUrl = $request->getUri();
        return new RedirectResponse($this->urlGenerator->generate('app_login', [
            'returnTo' => $targetUrl
        ]));
    }
}