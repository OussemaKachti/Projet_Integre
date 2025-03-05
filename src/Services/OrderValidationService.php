<?php
// src/services/OrderValidationService.php

namespace App\Services;

use App\Entity\Commande;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class OrderValidationService
{
    private MailerInterface $mailer;
    private EntityManagerInterface $entityManager;
    
    public function __construct(MailerInterface $mailer, EntityManagerInterface $entityManager)
    {
        $this->mailer = $mailer;
        $this->entityManager = $entityManager;
    }
    
    /**
     * Valide la commande :
     * - Envoie un e-mail à l'utilisateur
     * - Met à jour le statut de la commande de "en_cours" à "valide"
     */
    public function validateOrder(Commande $commande): void
    {
        // Vérifier que la commande est en cours
        if ($commande->getStatut() !== 'en_cours') {
            throw new \LogicException('La commande n\'est pas en cours.');
        }

        $user = $commande->getUser();
        if (!$user || !$user->getEmail()) {
            throw new \LogicException('L\'utilisateur de la commande ne possède pas d\'email.');
        }

        // Créer l'email
        $email = (new Email())
            ->from('noreply@votresite.com')
            ->to($user->getEmail())
            ->subject('Validation de votre commande')
            ->html('<p>Bonjour ' . $user->getNom() . ',</p>
                    <p>Votre commande #' . $commande->getId() . ' a été validée avec succès.</p>
                    <p>Merci pour votre confiance !</p>');

        // Envoyer l'email
        $this->mailer->send($email);

        // Mettre à jour le statut de la commande
        $commande->setStatut('valide');

        // Persister les modifications en base
        $this->entityManager->flush();
    }
}
