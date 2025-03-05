<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailService
{
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function sendEmail(string $to, string $subject, string $body): void
    {
        $email = (new Email())
            ->from('no-reply@votre-domaine.com') // Adresse expéditeur
            ->to($to) // Adresse destinataire
            ->subject($subject) // Sujet de l'e-mail
            ->html($body); // Corps de l'e-mail au format HTML

        $this->mailer->send($email);
    }
}