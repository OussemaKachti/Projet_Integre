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
    
    // List of paths that don't require authentication
    private $publicPaths = [
        '/login',
        '/logout',
        '/user/sign-up',
        '/home',
        '/',                 // Root path
        '/access-denied',
        '/confirm-email',
        
    ];

    // List of admin-only paths
    private $adminPaths = [
        '/admin'
    ];

    // FUTURE ENHANCEMENT: Add role-specific path restrictions
    // You can uncomment and customize these arrays when you're ready to implement more granular access control
    /*
    private $membrePaths = [
        '/membre-only'
    ];

    private $presidentClubPaths = [
        '/president-club'
    ];
    */

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

        // FUTURE ENHANCEMENT: Add role-specific access checks here
        // Example:
        /*
        // Check MEMBRE paths
        foreach ($this->membrePaths as $membrePath) {
            if (strpos($path, $membrePath) === 0) {
                if (!$this->security->isGranted('ROLE_MEMBRE')) {
                    $event->setResponse(new RedirectResponse($this->urlGenerator->generate('access_denied')));
                }
                return;
            }
        }

        // Check PRESIDENT_CLUB paths
        foreach ($this->presidentClubPaths as $presidentClubPath) {
            if (strpos($path, $presidentClubPath) === 0) {
                if (!$this->security->isGranted('ROLE_PRESIDENT_CLUB')) {
                    $event->setResponse(new RedirectResponse($this->urlGenerator->generate('access_denied')));
                }
                return;
            }
        }
        */

        // For all other paths, any authenticated user (including NON_MEMBRE) is allowed
        // No action needed here - the request will continue normally
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 20], // Priority before firewall
        ];
    }
}