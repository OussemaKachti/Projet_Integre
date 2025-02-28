<?php

namespace App\Service;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class UserConfirmationService
{
    private $urlGenerator;
    private $entityManager;
    private $mailer;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
    }

    public function sendConfirmationEmail(User $user): void
{
    // Generate a random token
    $token = bin2hex(random_bytes(32));
    
    // Set the token and expiration
    $user->setConfirmationToken($token);
    $user->setConfirmationTokenExpiresAt(new \DateTimeImmutable('+24 hours'));
    $user->setIsVerified(false); // Explicitly set as not verified
    
    // Save changes
    $this->entityManager->flush();

    // Generate the confirmation URL
    $confirmationUrl = $this->urlGenerator->generate(
        'confirm_email',
        ['token' => $token],
        UrlGeneratorInterface::ABSOLUTE_URL
    );

    // Create and send the email
    $email = (new TemplatedEmail())
        ->from('no-reply@uniclubs.tn')
        ->to($user->getEmail())
        ->subject('Confirm Your Email')
        ->htmlTemplate('security/confirmation.html.twig')
        ->context([
            'confirmationUrl' => $confirmationUrl,
            'user' => $user,
        ]);

    // Try-catch to capture specific errors
    try {
        $this->mailer->send($email);
    } catch (\Exception $e) {
        // Log the error or rethrow it
        throw new \Exception('Failed to send confirmation email: ' . $e->getMessage());
    }
}
}