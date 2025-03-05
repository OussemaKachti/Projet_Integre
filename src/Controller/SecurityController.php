<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
    #[Route('/access-denied', name: 'access_denied')]
    public function accessDenied(Request $request): Response
    {
        // Check if user is logged in
        if ($this->getUser()) {
            // Get the requested URL that was denied (if available)
            $deniedUrl = $request->query->get('returnUrl', null);
            
            // Check if the denied URL was an admin path 
            $isAdminPath = $deniedUrl && strpos($deniedUrl, '/admin') === 0;
            
            // If logged in but access denied, it's a permission issue
            return $this->render('security/insufficient_permissions.html.twig', [
                'isAdminPath' => $isAdminPath,
                'deniedUrl' => $deniedUrl
            ]);
        }
        
        // Get the return URL if provided
        $returnTo = $request->query->get('returnTo', null);
        
        // If not logged in, show the login required page
        return $this->render('security/access_denied.html.twig', [
            'returnTo' => $returnTo
        ]);
    }
    #[Route('/confirm-email/{token}', name: 'confirm_email')]
    public function confirmEmail(
        string $token,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator
    ): Response {
        // Find the user by the confirmation token
        $user = $userRepository->findOneBy(['confirmationToken' => $token]);
    
        if (!$user) {
            $this->addFlash('error', 'Invalid or expired confirmation link.');
            return $this->redirectToRoute('app_user_signup');
        }
    
        // Check if user is already verified
        if ($user->isVerified()) {
            $this->addFlash('info', 'This email address is already verified.');
            return $this->redirectToRoute('app_login');
        }
    
        try {
            // Mark the user as verified and clear the token
            $user->setIsVerified(true);
            $user->setConfirmationToken(null);
            
            // Update the user entity
            $entityManager->persist($user);
            $entityManager->flush();
    
            // Add success message
            $this->addFlash('success', 'Your email has been confirmed! You can now log in.');
    
            // Automatically log in the user if needed (optional)
            // return $this->redirectToRoute('app_home');
    
        } catch (\Exception $e) {
            $this->addFlash('error', 'There was an error confirming your email. Please try again.');
            return $this->redirectToRoute('app_user_signup');
        }
    
        // Redirect to login page
        return $this->redirectToRoute('app_login', [], 302);
    }


}
