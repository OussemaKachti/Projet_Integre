<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Services\OrderValidationService;
use App\Entity\Commande;
use App\Enum\StatutCommandeEnum;
use App\Entity\User;

#[AsCommand(name: 'app:send-test-email')]
class SendTestEmailCommand extends Command
{
    private EmailService $emailService;

    public function __construct(OrderValidationService $orderValidationService)
    {
        parent::__construct();
        $this->orderValidationService = $orderValidationService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $commande = new Commande(); // Tu dois récupérer une vraie commande depuis la base de données
        $commande->setStatut(StatutCommandeEnum::EN_COURS); // Simule une commande valide
        $commande->setUser($user); 
        $this->orderValidationService->validateOrder($commande);

        return Command::SUCCESS;
    }
}
