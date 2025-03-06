<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Enum\RoleEnum;
use App\Entity\User;

use App\Form\UserType;
use App\Service\UserConfirmationService;
use App\Service\ContentModerationService;
use App\Service\WarningEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\String\Slugger\SluggerInterface;
use Psr\Log\LoggerInterface;



#[Route(path: '/user')]
class UserController extends AbstractController
{

    private $logger;
    private $warningEmailService;
    private $entityManager;
    
    public function __construct(
        LoggerInterface $logger,
        WarningEmailService $warningEmailService,
        EntityManagerInterface $entityManager
    ) {
        $this->logger = $logger;
        $this->warningEmailService = $warningEmailService;
        $this->entityManager = $entityManager;
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
    
    /**
     * Process content moderation warning and handle account status
     */
    private function handleContentModeration(User $user, bool $isInappropriate, string $contentType, string $explanation): bool
    {
        if (!$isInappropriate) {
            return false; // No warning needed
        }
        
        // Clean explanation for user-friendly message
        $cleanExplanation = preg_replace('/^(YES|NO)\.\s*/i', '', $explanation);
        
        // Increment warning count
        $newWarningCount = $user->incrementWarningCount();
        $this->logger->info('User received content warning', [
            'user_id' => $user->getId(),
            'warning_count' => $newWarningCount,
            'content_type' => $contentType, 
            'reason' => $cleanExplanation
        ]);
        
        // Send warning email
        $this->warningEmailService->sendContentWarningEmail(
            $user, 
            $contentType, 
            $cleanExplanation
        );
        
        // Check if we need to disable the account
        if ($user->hasReachedMaxWarnings()) {
            // Set account status to disabled
            $user->setStatus(User::STATUS_DISABLED);
            
            $this->logger->warning('User account disabled due to maximum warnings', [
                'user_id' => $user->getId(),
                'warning_count' => $newWarningCount
            ]);
            
            // Send account disabled notification
            $this->warningEmailService->sendAccountDisabledEmail($user);
        }
        
        // Save changes to the database
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return true;
    }
    
    #[Route('/sign-up', name: 'app_user_signup')]
public function signUp(
    Request $request,
    EntityManagerInterface $entityManager,
    UserPasswordHasherInterface $passwordHasher,
    UserConfirmationService $userConfirmationService,
    ContentModerationService $contentModerationService
): Response {
    // Create a new User entity
    $user = new User();
    // Create the form using the UserType (without the role field)
    $form = $this->createForm(UserType::class, $user);
    // Handle form submission
    $form->handleRequest($request);

    if ($form->isSubmitted()) {
        // Current Mapping:
        // $user->getNom() = First Name (despite 'nom' usually meaning 'last name' in French)
        // $user->getPrenom() = Last Name (despite 'prenom' usually meaning 'first name' in French)
        
        // Get the entered field values (using the existing mapping)
        $firstName = $user->getNom();      // First name is stored in 'nom' field
        $lastName = $user->getPrenom();    // Last name is stored in 'prenom' field
        $fullName = $firstName . ' ' . $lastName;
        
        // Track validation errors
        $hasNameError = false;
        
        // IMPORTANT: Always check for inappropriate content first
        // This ensures content moderation happens regardless of other validation errors
        if ($firstName || $lastName) {
            $this->logger->debug('Checking name during signup', [
                'name' => $fullName
            ]);
            
            $nameCheckResult = $contentModerationService->checkUserTextWithFallback($fullName);
            
            $this->logger->debug('Name check result', $nameCheckResult);
            
            if ($nameCheckResult['is_inappropriate']) {
                $this->addFlash('error', 'The provided name contains inappropriate content: ' . $nameCheckResult['explanation']);
                $hasNameError = true;
                // Don't return early - let's also show any format errors
            }
        }
        
        // Check First Name (stored in 'nom' field)
        if ($firstName && preg_match('/\d/', $firstName)) {
            $form->get('nom')->addError(new FormError('First name cannot contain numbers'));
            $hasNameError = true;
        }
        
        if ($firstName && !preg_match('/^[a-zA-ZÀ-ÿ\s\'-]+$/u', $firstName)) {
            $form->get('nom')->addError(new FormError('First name can only contain letters, spaces, hyphens and apostrophes'));
            $hasNameError = true;
        }
        
        // Check Last Name (stored in 'prenom' field)
        if ($lastName && preg_match('/\d/', $lastName)) {
            $form->get('prenom')->addError(new FormError('Last name cannot contain numbers'));
            $hasNameError = true;
        }
        
        if ($lastName && !preg_match('/^[a-zA-ZÀ-ÿ\s\'-]+$/u', $lastName)) {
            $form->get('prenom')->addError(new FormError('Last name can only contain letters, spaces, hyphens and apostrophes'));
            $hasNameError = true;
        }
        
        // Only return if we have validation errors, but AFTER checking for inappropriate content
        if ($hasNameError) {
            return $this->render('user/sign-up.html.twig', [
                'form' => $form->createView(),
            ]);
        }
        
        // Then proceed with other form validation checks
        if ($form->isValid()) {
            // Rest of your controller code remains the same
            $hasValidationErrors = false;
            
            // Check for existing email before trying to persist
            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
            if ($existingUser) {
                // Add error directly to the email field
                $form->get('email')->addError(new FormError('This email is already registered. Please use a different email or login.'));
                $hasValidationErrors = true;
            }
            
            // Check for existing phone if phone is provided
            if ($user->getTel()) {
                $existingPhone = $entityManager->getRepository(User::class)->findOneBy(['tel' => $user->getTel()]);
                if ($existingPhone) {
                    $form->get('tel')->addError(new FormError('This phone number is already registered.'));
                    $hasValidationErrors = true;
                }
            }
            
            // If there are validation errors, return the form with all errors
            if ($hasValidationErrors) {
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

            try {
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
            } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
                // Fallback for any other unique constraint violations
                $this->logger->error('Unique constraint violation during registration', [
                    'error' => $e->getMessage(),
                    'user_email' => $user->getEmail()
                ]);
                
                if (strpos($e->getMessage(), 'UNIQ_8D93D649E7927C74') !== false) {
                    // Email constraint
                    $form->get('email')->addError(new FormError('This email is already registered. Please use a different email or login.'));
                } elseif (strpos($e->getMessage(), 'tel') !== false) {
                    // Phone constraint
                    $form->get('tel')->addError(new FormError('This phone number is already registered.'));
                } else {
                    $this->addFlash('error', 'Registration failed. This account information is already in use.');
                }
                
                return $this->render('user/sign-up.html.twig', [
                    'form' => $form->createView(),
                ]);
            } catch (\Exception $e) {
                // Generic exception handling
                $this->logger->error('Error during registration', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $this->addFlash('error', 'An error occurred during registration. Please try again later.');
                
                return $this->render('user/sign-up.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
        }
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
        ContentModerationService $contentModerationService,
        ValidatorInterface $validator
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
        
        // Current Mapping:
        // nom = First Name (despite meaning "last name" in French)
        // prenom = Last Name (despite meaning "first name" in French)
        
        // Extract first and last name according to mapping
        $firstName = $nameParts[0]; // Will be saved to 'nom' field
        $lastName = implode(' ', array_slice($nameParts, 1)); // Will be saved to 'prenom' field
        
        // Validate first name (to be saved in 'nom' field)
        if (preg_match('/\d/', $firstName)) {
            $this->addFlash('error', 'First name cannot contain numbers');
            return $this->redirectToRoute('app_profile');
        }
        
        if (!preg_match('/^[a-zA-ZÀ-ÿ\s\'-]+$/u', $firstName)) {
            $this->addFlash('error', 'First name can only contain letters, spaces, hyphens and apostrophes');
            return $this->redirectToRoute('app_profile');
        }
        
        // Validate last name (to be saved in 'prenom' field)
        if (preg_match('/\d/', $lastName)) {
            $this->addFlash('error', 'Last name cannot contain numbers');
            return $this->redirectToRoute('app_profile');
        }
        
        if (!preg_match('/^[a-zA-ZÀ-ÿ\s\'-]+$/u', $lastName)) {
            $this->addFlash('error', 'Last name can only contain letters, spaces, hyphens and apostrophes');
            return $this->redirectToRoute('app_profile');
        }
        
        // Add content moderation check for name with fallback for better reliability
        $nameCheckResult = $contentModerationService->checkUserTextWithFallback($fullName);
        
        $this->logger->debug('Name moderation during profile update', [
            'name' => $fullName,
            'result' => $nameCheckResult
        ]);
        
        if ($nameCheckResult['is_inappropriate']) {
            // Process warning and check account status
            $this->handleContentModeration(
                $user, 
                true, 
                'profile name', 
                $nameCheckResult['explanation'] ?? 'Inappropriate content detected'
            );
            
            // Extra safeguard to remove any remaining YES/NO prefixes
            $explanation = preg_replace('/^(YES|NO)\.\s*/i', '', $nameCheckResult['explanation']);
            
            $this->addFlash('error', 'The provided name contains inappropriate content: ' . $explanation);
            
            // If account was disabled, redirect to logout
            if (!$user->isActive()) {
                $this->addFlash('error', 'Your account has been disabled due to multiple content policy violations.');
                return $this->redirectToRoute('app_logout');
            }
            
            return $this->redirectToRoute('app_profile');
        }
        
        // Rest of the code remains the same...
        // Validate email, phone, etc.
        
        // Check if any data has changed
        $hasChanges = false;
        
        // Remember: nom = First Name, prenom = Last Name in your mapping
        if ($firstName !== $user->getNom()) {
            $user->setNom($firstName);
            $hasChanges = true;
        }
        
        if ($lastName !== $user->getPrenom()) {
            $user->setPrenom($lastName);
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
            try {
                $entityManager->persist($user);
                $entityManager->flush();
                $this->addFlash('success', 'Profile updated successfully');
            } catch (\Exception $e) {
                $this->logger->error('Profile update failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $this->addFlash('error', 'Failed to update profile: ' . $e->getMessage());
            }
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
        ContentModerationService $contentModerationService,
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
            
            // Use the enhanced multi-level moderation approach
            
            // 1. First check with comprehensive moderation with fallbacks
            $moderationResult = $contentModerationService->checkProfilePicture($imagePath);
            
            // 2. Also check with specialized NSFW model for better reliability
            $nsfwResult = $contentModerationService->checkImageWithNSFWModel($imagePath);
            
            // Log both results - use internal_explanation for logging if available
            $logger->debug('Content moderation results', [
                'comprehensive_result' => isset($moderationResult['internal_explanation']) ? 
                    array_merge($moderationResult, ['debug_explanation' => $moderationResult['internal_explanation']]) : 
                    $moderationResult,
                'nsfw_result' => isset($nsfwResult['internal_explanation']) ? 
                    array_merge($nsfwResult, ['debug_explanation' => $nsfwResult['internal_explanation']]) : 
                    $nsfwResult
            ]);
            
            // FIXED: Consider image inappropriate if EITHER check flags it
            $isInappropriate = false;
            $explanation = '';
            
            // First check NSFW model result - this is the most reliable one
            if (isset($nsfwResult['is_inappropriate']) && $nsfwResult['is_inappropriate'] === true) {
                $isInappropriate = true;
                $explanation = isset($nsfwResult['explanation']) ? 
                    $nsfwResult['explanation'] : 
                    'NSFW content detected';
                $logger->info('Image rejected by NSFW model', [
                    'explanation' => isset($nsfwResult['internal_explanation']) ? 
                        $nsfwResult['internal_explanation'] : 
                        ($nsfwResult['explanation'] ?? 'No explanation provided')
                ]);
            }
            
            // If NSFW passed, check comprehensive result if it's successful
            if (!$isInappropriate && 
                isset($moderationResult['success']) && $moderationResult['success'] === true && 
                isset($moderationResult['is_inappropriate']) && $moderationResult['is_inappropriate'] === true) {
                $isInappropriate = true;
                $explanation = isset($moderationResult['explanation']) ? 
                    $moderationResult['explanation'] : 
                    'Inappropriate content detected';
                $logger->info('Image rejected by comprehensive moderation', [
                    'explanation' => isset($moderationResult['internal_explanation']) ? 
                        $moderationResult['internal_explanation'] : 
                        ($moderationResult['explanation'] ?? 'No explanation provided')
                ]);
            }
            
            if ($isInappropriate) {
                // Process warning and check account status
                $this->handleContentModeration(
                    $user, 
                    true, 
                    'profile picture', 
                    $explanation
                );
                
                // Remove the inappropriate image
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
                
                // Generate a clean explanation for the user
                $cleanExplanation = '';
                if (isset($nsfwResult['is_inappropriate']) && $nsfwResult['is_inappropriate']) {
                    $cleanExplanation = $nsfwResult['explanation'] ?? 'NSFW content detected';
                } elseif (isset($moderationResult['is_inappropriate']) && $moderationResult['is_inappropriate']) {
                    $cleanExplanation = $moderationResult['explanation'] ?? 'Inappropriate content detected';
                } else {
                    $cleanExplanation = 'The image does not meet our content guidelines';
                }
                
                // Extra safeguard: Remove any YES/NO prefixes from the explanation
                $cleanExplanation = preg_replace('/^(YES|NO)\.\s*/i', '', $cleanExplanation);
                
                // If account was disabled, redirect to logout
                if (!$user->isActive()) {
                    $this->addFlash('error', 'Your account has been disabled due to multiple content policy violations.');
                    return $this->redirectToRoute('app_logout');
                }
                
                // Add flash message with the cleaned explanation
                $this->addFlash('error', 'The uploaded profile picture contains inappropriate content and was rejected. Reason: ' . $cleanExplanation);
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
                ->from('no-reply@uniclubs.com') // Replace with your sender email
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