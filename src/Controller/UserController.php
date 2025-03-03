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
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

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
    public function updateProfile(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        // Get current password for verification
        $currentPassword = $request->request->get('current_password');
        
        // Verify the current password
        if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
            $this->addFlash('error', 'Current password is incorrect');
            return $this->redirectToRoute('app_profile');
        }
        
        // Get form data
        $fullName = $request->request->get('full_name');
        $email = $request->request->get('email');
        $phone = $request->request->get('phone');
        
        // Validate full name
        $nameParts = explode(' ', trim($fullName));
        if (count($nameParts) < 2 || empty($nameParts[0]) || empty($nameParts[1])) {
            $this->addFlash('error', 'Please provide both first and last name');
            return $this->redirectToRoute('app_profile');
        }
        
        // Set first and last name
        $firstName = $nameParts[0];
        $lastName = implode(' ', array_slice($nameParts, 1));
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', 'Email address is not valid');
            return $this->redirectToRoute('app_profile');
        }
        
        // Check if email is already in use by another user
        if ($email !== $user->getEmail()) {
            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                $this->addFlash('error', 'The email address is already in use');
                return $this->redirectToRoute('app_profile');
            }
        }
        
        // Validate phone number (Tunisian format)
        $phonePattern = '/^((\+|00)216)?([2579][0-9]{7}|(3[012]|4[01]|8[0128])[0-9]{6}|42[16][0-9]{5})$/';
        if (!empty($phone) && !preg_match($phonePattern, $phone)) {
            $this->addFlash('error', 'Invalid phone number format');
            return $this->redirectToRoute('app_profile');
        }
        
        // Check if any data has changed
        $hasChanges = false;
        
        if ($firstName !== $user->getPrenom()) {
            $user->setPrenom($firstName);
            $hasChanges = true;
        }
        
        if ($lastName !== $user->getNom()) {
            $user->setNom($lastName);
            $hasChanges = true;
        }
        
        if ($email !== $user->getEmail()) {
            $user->setEmail($email);
            $hasChanges = true;
        }
        
        if ($phone !== $user->getTel()) {
            $user->setTel($phone);
            $hasChanges = true;
        }
        
        // Only persist if there are changes
        if ($hasChanges) {
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', 'Profile updated successfully');
        } else {
            $this->addFlash('info', 'No changes were made to your profile');
        }
        
        return $this->redirectToRoute('app_profile');
    }

    #[Route('/change-password', name: 'app_change_password', methods: ['POST'])]
public function changePassword(
    Request $request,
    UserPasswordHasherInterface $passwordHasher,
    Security $security,
    EntityManagerInterface $entityManager,
    SessionInterface $session,
    MailerInterface $mailer,
    \Twig\Environment $twig // Inject Twig to render the template
): Response {
    // Set a flag in the session to keep the password tab active
    $session->set('password_tab_active', true);

    // Get the logged-in user
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

    // Check if new password is the same as the old password
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
    try {
        $entityManager->persist($user);
        $entityManager->flush();

        // Combine `prenom` and `nom` to create the full name
        $fullName = $user->getPrenom() . ' ' . $user->getNom();

        // Render the Twig template for the email
        $emailBody = $twig->render('security/password_change_notification.html.twig', [
            'fullName' => $fullName, // Pass the combined full name to the template
        ]);

        // Create and send the email
        $email = (new Email())
            ->from('no-reply@yourdomain.com') // Replace with your sender email
            ->to($user->getEmail()) // Use the user's email
            ->subject('Password Changed Successfully')
            ->html($emailBody); // Use ->html() instead of ->text()

        $mailer->send($email);

        $this->addFlash('success', 'Password updated successfully');
    } catch (\Exception $e) {
        $this->addFlash('error', 'An error occurred while updating your password');
    }

    return $this->redirectToRoute('app_profile');
}
}
