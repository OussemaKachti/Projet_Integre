<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Enum\RoleEnum;
use App\Entity\User;
use App\Form\ProfileFormType;
use App\Form\UserType;
use App\Service\UserConfirmationService;
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

    #[Route('/admin', name: 'app_admin')]
    public function admin(): Response
    {
        return $this->render('admin.html.twig');
    }
    #[Route('/profile', name: 'app_profile')]
    public function index(Request $request, SessionInterface $session): Response
    {
        // Check if user is authenticated
        $user = $this->getUser();
        if (!$user) {
            // If not authenticated, redirect to access denied page
            return $this->redirectToRoute('access_denied');
        }

        if ($request->query->get('cleanup')) {
            $session->remove('password_tab_active');
        }
        // Now it's safe to pass $user to the template
        return $this->render('user/profile.html.twig', [
            'user' => $user,
        ]);
    }
    #[Route('/sign-up', name: 'app_user_signup')]
    public function signUp(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        UserConfirmationService $userConfirmationService // Add this dependency
    ): Response {
        // Create a new User entity
        $user = new User();
        // Create the form using the UserType (without the role field)
        $form = $this->createForm(UserType::class, $user);
        // Handle form submission
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash the password
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

            // Automatically send the confirmation email
            try {
                $userConfirmationService->sendConfirmationEmail($user);
                $this->addFlash('success', 'Check your email to confirm your account!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Failed to send confirmation email. Please contact support.');
            }

            // Redirect to success page
            return $this->redirectToRoute('app_home');
        }

        // Render the form template
        return $this->render('user/sign-up.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/update-profile', name: 'app_update_profile', methods: ['POST'])]
    public function updateProfile(
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You need to be logged in to update your profile.');
        }

        // Store original values in case of failure
        $originalData = [
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'email' => $user->getEmail(),
            'tel' => $user->getTel()
        ];

        try {
            // Handle full name
            $fullName = $request->request->get('full_name');
            if (!empty($fullName)) {
                $nameParts = explode(' ', trim($fullName), 2);
                if (count($nameParts) === 2) {
                    $user->setPrenom(trim($nameParts[0]));
                    $user->setNom(trim($nameParts[1]));
                }
            }

            // Handle other fields
            $user->setEmail($request->request->get('email', $user->getEmail()));
            $user->setTel($request->request->get('phone', $user->getTel()));

            // Validate using entity constraints
            $errors = $validator->validate($user);

            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $field = $error->getPropertyPath();
                    $message = $error->getMessage();

                    // Map entity fields to form fields
                    $formField = match ($field) {
                        'nom' => 'full_name',
                        'prenom' => 'full_name',
                        'email' => 'email',
                        'tel' => 'phone',
                        default => 'general'
                    };

                    $this->addFlash("error_$formField", $message);
                }

                // Restore original values
                $user->setNom($originalData['nom']);
                $user->setPrenom($originalData['prenom']);
                $user->setEmail($originalData['email']);
                $user->setTel($originalData['tel']);

                return $this->redirectToRoute('app_profile');
            }

            $entityManager->flush();
            $this->addFlash('success', 'Profile updated successfully');
        } catch (\Exception $e) {
            $this->addFlash('error', 'An error occurred while updating your profile');
        }

        return $this->redirectToRoute('app_profile');
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
        // Check if new password is same as old password
        if ($passwordHasher->isPasswordValid($user, $newPassword)) {
            $this->addFlash('error', 'New password must be different from current password');
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

        try {
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', 'Password updated successfully');
        } catch (\Exception $e) {
            $this->addFlash('error', 'An error occurred while updating your password');
        }
        return $this->redirectToRoute('app_profile');
    }
}
