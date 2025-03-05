<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Enum\RoleEnum;
use App\Entity\User;

use App\Form\UserType;
use App\Service\UserConfirmationService;
use App\Service\LLaVAContentModerationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\String\Slugger\SluggerInterface;
use Psr\Log\LoggerInterface;


#[Route(path: '/user')]
class UserController extends AbstractController
{

    private $logger;
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
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
        UserConfirmationService $userConfirmationService,
        LLaVAContentModerationService $contentModerationService
    ): Response {
        // Create a new User entity
        $user = new User();
        // Create the form using the UserType (without the role field)
        $form = $this->createForm(UserType::class, $user);
        // Handle form submission
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check name for inappropriate content
            $fullName = $user->getPrenom() . ' ' . $user->getNom();
            
            $this->logger->debug('Checking name during signup', [
                'name' => $fullName
            ]);
            
            $nameCheckResult = $contentModerationService->checkUserText($fullName);
            
            $this->logger->debug('Name check result', $nameCheckResult);
            
            if ($nameCheckResult['is_inappropriate']) {
                $this->addFlash('error', 'The provided name contains inappropriate content: ' . $nameCheckResult['explanation']);
                return $this->render('user/sign-up.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
            
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
                $this->logger->error('Failed to send confirmation email', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->getId()
                ]);
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
        UserPasswordHasherInterface $passwordHasher, 
        EntityManagerInterface $entityManager,
        LLaVAContentModerationService $contentModerationService
    ): Response {
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
        
        // Add content moderation check for name
        $nameCheckResult = $contentModerationService->checkUserText($fullName);
        
        $this->logger->debug('Name moderation during profile update', [
            'name' => $fullName,
            'result' => $nameCheckResult
        ]);
        
        if ($nameCheckResult['is_inappropriate']) {
            $this->addFlash('error', 'The provided name contains inappropriate content: ' . $nameCheckResult['explanation']);
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

    #[Route('/update-profile-picture', name: 'app_update_profile_picture', methods: ['POST'])]
    public function updateProfilePicture(
        Request $request, 
        UserPasswordHasherInterface $passwordHasher, 
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        LLaVAContentModerationService $contentModerationService,
        LoggerInterface $logger
    ): Response {
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
        
        // Handle profile picture upload
        $profilePicture = $request->files->get('profile_picture');
        if (!$profilePicture) {
            $this->addFlash('error', 'No profile picture was uploaded');
            return $this->redirectToRoute('app_profile');
        }
        
        // Add file type validation
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($profilePicture->getMimeType(), $allowedTypes)) {
            $this->addFlash('error', 'Only JPG, PNG, and GIF images are allowed');
            return $this->redirectToRoute('app_profile');
        }
        
        // Add size limit (5MB)
        if ($profilePicture->getSize() > 5 * 1024 * 1024) {
            $this->addFlash('error', 'Image size must not exceed 5MB');
            return $this->redirectToRoute('app_profile');
        }
        
        $originalFilename = pathinfo($profilePicture->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$profilePicture->guessExtension();
        
        try {
            $profilePicturesDir = $this->getParameter('profile_pictures_directory');
            
            // Create directory if it doesn't exist
            if (!file_exists($profilePicturesDir)) {
                mkdir($profilePicturesDir, 0755, true);
            }
            
            $profilePicture->move(
                $profilePicturesDir,
                $newFilename
            );
            
            // Full path to the uploaded image
            $imagePath = $profilePicturesDir.'/'.$newFilename;
            
            // Check with multiple moderation services for better reliability
            
            // 1. Check with LLaVA
            $llavaResult = $contentModerationService->checkProfilePicture($imagePath);
            
            // 2. Also check with specialized NSFW model
            $nsfwResult = $contentModerationService->checkImageWithNSFWModel($imagePath);
            
            // Log both results
            $logger->debug('Content moderation results', [
                'llava_result' => $llavaResult,
                'nsfw_result' => $nsfwResult
            ]);
            
            // FIXED: Consider image inappropriate if EITHER check flags it
            $isInappropriate = false;
            
            // First check NSFW model result - this is the most reliable one
            if (isset($nsfwResult['is_inappropriate']) && $nsfwResult['is_inappropriate'] === true) {
                $isInappropriate = true;
                $logger->info('Image rejected by NSFW model', [
                    'explanation' => $nsfwResult['explanation'] ?? 'No explanation provided'
                ]);
            }
            
            // If NSFW passed, check LLaVA result if it's successful
            if (!$isInappropriate && 
                isset($llavaResult['success']) && $llavaResult['success'] === true && 
                isset($llavaResult['is_inappropriate']) && $llavaResult['is_inappropriate'] === true) {
                $isInappropriate = true;
                $logger->info('Image rejected by LLaVA model', [
                    'explanation' => $llavaResult['explanation'] ?? 'No explanation provided'
                ]);
            }
            
            // Add a final safety check for very large images - they might be suspicious
            $imageSize = filesize($imagePath) / 1024 / 1024; // Size in MB
            if (!$isInappropriate && $imageSize > 5) { // If over 5MB
                $logger->warning('Large image upload detected', [
                    'size_mb' => $imageSize,
                    'path' => $imagePath
                ]);
                // You can choose to reject large images or just log them
                // $isInappropriate = true;
            }
            
            if ($isInappropriate) {
                // Remove the inappropriate image
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
                
                // Generate a clear explanation for the user
                $explanation = '';
                if (isset($nsfwResult['is_inappropriate']) && $nsfwResult['is_inappropriate']) {
                    $explanation = $nsfwResult['explanation'] ?? 'NSFW content detected';
                } elseif (isset($llavaResult['is_inappropriate']) && $llavaResult['is_inappropriate']) {
                    $explanation = $llavaResult['explanation'] ?? 'Inappropriate content detected';
                } else {
                    $explanation = 'The image does not meet our content guidelines';
                }
                
                // Add flash message with the explanation
                $this->addFlash('error', 'The uploaded profile picture contains inappropriate content and was rejected. Reason: ' . $explanation);
                return $this->redirectToRoute('app_profile');
            }
            
            // If previous profile picture exists, remove it
            if ($user->getProfilePicture()) {
                $oldImagePath = $profilePicturesDir.'/'.$user->getProfilePicture();
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
            
            // Update user profile picture
            $user->setProfilePicture($newFilename);
            $entityManager->persist($user);
            $entityManager->flush();
            
            $this->addFlash('success', 'Profile picture updated successfully');
        } catch (\Exception $e) {
            $logger->error('Profile picture update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Delete the uploaded file if it exists
            if (isset($imagePath) && file_exists($imagePath)) {
                unlink($imagePath);
            }
            
            $this->addFlash('error', 'Failed to upload profile picture: ' . $e->getMessage());
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