<?php

namespace App\Service;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class WarningEmailService
{
    private MailerInterface $mailer;
    private Environment $twig;
    private LoggerInterface $logger;
    private string $senderEmail;

    public function __construct(
        MailerInterface $mailer,
        Environment $twig,
        LoggerInterface $logger,
        string $senderEmail = 'no-reply@yourdomain.com'
    ) {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->logger = $logger;
        $this->senderEmail = $senderEmail;
    }

    /**
     * Send a warning email to the user based on their warning count
     */
    public function sendContentWarningEmail(User $user, string $contentType, string $reason): bool
    {
        $warningCount = $user->getWarningCount();
        
        // Determine warning level
        $template = 'emails/content_warning.html.twig';
        $subject = match($warningCount) {
            1 => 'First Warning: Inappropriate Content Detected',
            2 => 'Second Warning: Inappropriate Content Detected',
            default => 'Final Warning: Inappropriate Content Detected',
        };
        
        // Maximum warnings reached - this will be their final warning
        $isFinal = $warningCount >= 2;
        
        try {
            $emailBody = $this->twig->render($template, [
                'user' => $user,
                'fullName' => $user->getPrenom() . ' ' . $user->getNom(),
                'contentType' => $contentType,
                'reason' => $reason,
                'warningCount' => $warningCount,
                'isFinal' => $isFinal,
            ]);

            $email = (new Email())
                ->from($this->senderEmail)
                ->to($user->getEmail())
                ->subject($subject)
                ->html($emailBody);

            $this->mailer->send($email);
            
            $this->logger->info('Content warning email sent', [
                'user_id' => $user->getId(),
                'warning_count' => $warningCount,
                'content_type' => $contentType,
                'reason' => $reason
            ]);
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to send content warning email', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId()
            ]);
            
            return false;
        }
    }

    /**
     * Send account disabled notification
     */
    public function sendAccountDisabledEmail(User $user): bool
    {
        try {
            $emailBody = $this->twig->render('emails/account_disabled.html.twig', [
                'user' => $user,
                'fullName' => $user->getPrenom() . ' ' . $user->getNom(),
            ]);

            $email = (new Email())
                ->from($this->senderEmail)
                ->to($user->getEmail())
                ->subject('Your Account Has Been Disabled')
                ->html($emailBody);

            $this->mailer->send($email);
            
            $this->logger->info('Account disabled email sent', [
                'user_id' => $user->getId()
            ]);
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to send account disabled email', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId()
            ]);
            
            return false;
        }
    }
}