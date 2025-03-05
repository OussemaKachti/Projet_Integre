<?php

namespace App\Service;

use App\Repository\CompetitionRepository;
use App\Repository\MissionProgressRepository;
use Doctrine\ORM\EntityManagerInterface;

class MissionCompletionChecker
{
    private CompetitionRepository $competitionRepository;
    private MissionProgressRepository $progressRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        CompetitionRepository $competitionRepository, 
        MissionProgressRepository $progressRepository, 
        EntityManagerInterface $entityManager
    ) {
        $this->competitionRepository = $competitionRepository;
        $this->progressRepository = $progressRepository;
        $this->entityManager = $entityManager;
    }

    public function checkAndUpdateMissionStatus(): void
    {
        // ✅ Get all activated competitions
        $competitions = $this->competitionRepository->findBy(['status' => 'activated']);

        foreach ($competitions as $competition) {
            // Get all progress entries for this competition
            $progressEntries = $this->progressRepository->findBy(['competition' => $competition]);

            $updated = false; // ✅ Track if we need to flush

            foreach ($progressEntries as $progress) {
                if ($progress->getProgress() >= $competition->getGoal() && !$progress->getIsCompleted()) {
                    // ✅ Mark only this club's mission as completed
                    $progress->setIsCompleted(true);

                    // ✅ Award points only to this club
                    $club = $progress->getClub();
                    $club->setPoints($club->getPoints() + $competition->getPoints());

                    // ✅ Persist changes
                    $this->entityManager->persist($progress);
                    $this->entityManager->persist($club);

                    $updated = true; // ✅ We made changes, so we need to flush

                    // ✅ Log the update (optional)
                }
            }

            // ✅ Flush only if there were updates
            if ($updated) {
                $this->entityManager->flush();
            }

           

            
        }

        
    }
}
