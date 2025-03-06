<?php
// src/Service/MissionCompletionChecker.php

namespace App\Service;

use App\Repository\CompetitionRepository;
use App\Repository\MissionProgressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class MissionCompletionChecker
{
    private CompetitionRepository $competitionRepository;
    private MissionProgressRepository $progressRepository;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(
        CompetitionRepository $competitionRepository, 
        MissionProgressRepository $progressRepository, 
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->competitionRepository = $competitionRepository;
        $this->progressRepository = $progressRepository;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function checkAndUpdateMissionStatus(): void
    {
        // Get all activated competitions
        $competitions = $this->competitionRepository->findBy(['status' => 'activated']);
        $this->logger->info('Checking missions for ' . count($competitions) . ' active competitions');

        foreach ($competitions as $competition) {
            // Get all progress entries for this competition
            $progressEntries = $this->progressRepository->findBy([
                'competition' => $competition,
                'isCompleted' => false // Only check incomplete missions
            ]);

            $updated = false;

            foreach ($progressEntries as $progress) {
                if ($progress->isGoalReached() && !$progress->getIsCompleted()) {
                    // Mark mission as completed
                    $progress->setIsCompleted(true);

                    // Award points to club
                    $club = $progress->getClub();
                    $club->setPoints($club->getPoints() + $competition->getPoints());

                    // Persist changes
                    $this->entityManager->persist($progress);
                    $this->entityManager->persist($club);

                    $updated = true;
                    
                    $this->logger->info(sprintf(
                        'Mission completed: Club %s completed mission %s and received %d points',
                        $club->getNomC(),
                        $competition->getNomComp(),
                        $competition->getPoints()
                    ));
                }
            }

            // Flush only if there were updates
            if ($updated) {
                $this->entityManager->flush();
            }
        }
    }
    
    // Add this method to check a specific progress entry
    public function checkSingleProgress($progress): bool
    {
        if ($progress->isGoalReached() && !$progress->getIsCompleted()) {
            // Mark mission as completed
            $progress->setIsCompleted(true);

            // Award points to club
            $club = $progress->getClub();
            $club->setPoints($club->getPoints() + $progress->getCompetition()->getPoints());

            // Persist changes
            $this->entityManager->persist($progress);
            $this->entityManager->persist($club);
            $this->entityManager->flush();

            $this->logger->info(sprintf(
                'Mission completed: Club %s completed mission %s and received %d points',
                $club->getNomC(),
                $progress->getCompetition()->getNomComp(),
                $progress->getCompetition()->getPoints()
            ));
            
            return true;
        }
        
        return false;
    }
}