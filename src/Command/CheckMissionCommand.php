<?php
// src/Command/CheckMissionsCommand.php

namespace App\Command;

use App\Service\MissionCompletionChecker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:check-missions',
    description: 'Check and update mission completion status',
)]
class CheckMissionCommand extends Command
{
    private MissionCompletionChecker $completionChecker;

    public function __construct(MissionCompletionChecker $completionChecker)
    {
        parent::__construct();
        $this->completionChecker = $completionChecker;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->note('Starting mission completion check...');

        $this->completionChecker->checkAndUpdateMissionStatus();

        $io->success('Mission completion check finished.');

        return Command::SUCCESS;
    }
}