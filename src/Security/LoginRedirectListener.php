<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LoginRedirectListener implements EventSubscriberInterface
{
    private $security;
    private $urlGenerator;
    
    // List of paths that don't require authentication - simplified for clarity
    private $publicPaths = [
        '/login',
        '/logout',
        '/user/sign-up',
        '/home',
        '/',                 // Root path
        '/access-denied',
        '/confirm-email',
        '/assets/',
    ];

    // List of admin-only paths
    private $adminPaths = [
        '/admin'
    ];

    // List of reset password paths - explicitly handled
    private $resetPasswordPaths = [
        '/reset-password'
    ];

    public function __construct(Security $security, UrlGeneratorInterface $urlGenerator)
    {
        $this->security = $security;
        $this->urlGenerator = $urlGenerator;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();
        
        // SPECIAL CASE: Always allow reset password paths
        foreach ($this->resetPasswordPaths as $resetPath) {
            if (strpos($path, $resetPath) === 0) {
                return; // Exit immediately - no checks for reset password
            }
        }
        
        // Skip for public paths - always accessible
        foreach ($this->publicPaths as $publicPath) {
            if (strpos($path, $publicPath) === 0) {
                return;
            }
        }
        
        // If user is not authenticated at all, redirect to access denied
        if (!$this->security->getUser()) {
            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('access_denied')));
            return;
        }

        // For admin paths, check if user has admin role
        foreach ($this->adminPaths as $adminPath) {
            if (strpos($path, $adminPath) === 0) {
                // Check if user has admin role
                if (!$this->security->isGranted('ROLE_ADMINISTRATEUR')) {
                    $event->setResponse(new RedirectResponse($this->urlGenerator->generate('access_denied')));
                }
                return;
            }
        }

        // For all other paths, any authenticated user is allowed
        // No action needed here - the request will continue normally
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 20], // Priority before firewall
        ];
    }
}