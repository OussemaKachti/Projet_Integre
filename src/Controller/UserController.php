<?php

namespace App\Controller;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Enum\RoleEnum;
use App\Entity\User;
use App\Form\UserType;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route(path: '/user')]
class UserController extends AbstractController
{

    // #[Route('/admin', name: 'app_admin')]
    // public function admin(): Response
    // {
    //     return $this->render('admin.html.twig');
    // }
    #[Route('/profile', name: 'app_profile')]
    public function index(Request $request, SessionInterface $session): Response
    {
        // Check if user is authenticated
        $user = $this->getUser();
        if (!$user) {
            // If not authenticated, redirect to access denied page
            return $this->redirectToRoute('access_denied');
        }
        
        if ($request->query->has('cleanup')) {
            $session->remove('password_tab_active');
            return new Response('', Response::HTTP_NO_CONTENT);
        }
        
        // Now it's safe to pass $user to the template
        return $this->render('user/profile.html.twig', [
            'user' => $user,
        ]);
    }
    #[Route('/sign-up', name: 'app_user_signup')]
    public function signUp(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        // Create a new User entity
        $user = new User();

        // Create the form using the UserType (without the role field)
        $form = $this->createForm(UserType::class, $user);

        // Handle form submission
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $form->get('password')->getData()
            );
            $user->setPassword($hashedPassword);
            // Set the default role to NON_MEMBRE
            $user->setRole(RoleEnum::NON_MEMBRE);

            // Persist the user to the database
            $entityManager->persist($user);
            $entityManager->flush();

            // Redirect to success page
            return $this->redirectToRoute('app_home');
        }

        // Render the form template
        return $this->render('user/sign-up.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/change-password', name: 'app_change_password', methods: ['POST'])]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        Security $security,
        EntityManagerInterface $entityManager,
        SessionInterface $session
    ): Response {
        // Set a flag in the session to keep the password tab active
        $session->set('password_tab_active', true);
        // Get the logged in user
        $user = $security->getUser();

        if (!$user) {
            $this->addFlash('error', 'You must be logged in to change your password');
            return $this->redirectToRoute('app_login');
        }

        // Get form data
        $oldPassword = $request->request->get('oldPassword');
        $newPassword = $request->request->get('newPassword');
        $confirmPassword = $request->request->get('confirmPassword');

        // Verify old password
        if (!$passwordHasher->isPasswordValid($user, $oldPassword)) {
            $this->addFlash('error', 'Current password is incorrect');
            return $this->redirectToRoute('app_profile');
        }

        // Check if new passwords match
        if ($newPassword !== $confirmPassword) {
            $this->addFlash('error', 'New passwords do not match');
            return $this->redirectToRoute('app_profile');
        }

        // Hash new password
        $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);

        // Save to database
        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('success', 'Password updated successfully');
        return $this->redirectToRoute('app_profile');

    }
    #[Route('/update-profile', name: 'app_update_profile', methods: ['POST'])]
    public function updateProfile(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        // Get the current user
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You need to be logged in to update your profile.');
        }
        
        // Create a copy of the original data in case validation fails
        $originalData = [
            'email' => $user->getEmail(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'tel' => $user->getTel()
        ];
        
        // Retrieve the submitted data
        $fullName = $request->request->get('full_name');
        $email = $request->request->get('email');
        $phone = $request->request->get('phone');
        
        $hasErrors = false;
        
        // Handle full name update
        if (!empty($fullName)) {
            $nameParts = explode(' ', trim($fullName), 2);
            
            if (count($nameParts) !== 2 || empty($nameParts[0]) || empty($nameParts[1])) {
                $this->addFlash('error', 'Please provide both first and last name.');
                $hasErrors = true;
            } else {
                $user->setNom(trim($nameParts[0]));
                $user->setPrenom(trim($nameParts[1]));
            }
        }
        
        // Handle email update
        if (!empty($email)) {
            $user->setEmail($email);
        }
        
        // Handle phone number update
        if (!empty($phone)) {
            $user->setTel($phone);
        }
        
        // Validate the user entity
        $errors = $validator->validate($user);
        
        if (count($errors) > 0 || $hasErrors) {
            // Restore original data if validation fails
            $user->setEmail($originalData['email']);
            $user->setNom($originalData['nom']);
            $user->setPrenom($originalData['prenom']);
            $user->setTel($originalData['tel']);
            
            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
            
            // Return to profile page with error messages
            return $this->render('user/profile.html.twig', [
                'user' => $user
            ]);
        }
        
        // Persist the changes to the database
        $entityManager->flush();
        
        // Add a success message
        $this->addFlash('success', 'Your profile has been updated successfully.');
        
        // Redirect to the profile page
        return $this->redirectToRoute('app_profile');
    }}
