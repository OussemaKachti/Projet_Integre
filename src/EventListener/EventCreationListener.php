<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Psr\Log\LoggerInterface;

use App\Entity\Evenement;
use App\Repository\MissionProgressRepository;
use App\Repository\CompetitionRepository;
use App\Service\MissionCompletionChecker;
use Doctrine\ORM\EntityManagerInterface;
use App\Enum\GoalTypeEnum;
class EventCreationListener
{
    private MissionProgressRepository $progressRepository;
    private CompetitionRepository $competitionRepository;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(
        MissionProgressRepository $progressRepository,
        CompetitionRepository $competitionRepository,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->progressRepository = $progressRepository;
        $this->competitionRepository = $competitionRepository;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $event = $args->getObject();

        // Only proceed if the entity is an Evenement
        if (!$event instanceof Evenement) {
            return;
        }
        $this->logger->info('EventCreationListener triggered for event: ' . $event->getId());
        $club = $event->getClub();

        if (!$club) {
            $this->logger->warning('Event has no associated club.');
            return; // No club, nothing to update
        }

        // Find all activated competitions for this club with EVENT_COUNT goal type
        $competitions = $this->competitionRepository->findBy([
            'status' => 'activated',
            'goalType' => GoalTypeEnum::EVENT_COUNT, // Ensure this matches your enum
        ]);

        foreach ($competitions as $competition) {
            // Find the MissionProgress for this club and competition
            $progress = $this->progressRepository->findOneBy([
                'club' => $club,
                'competition' => $competition,
                'isCompleted' => false,
            ]);

            if ($progress) {
                // Increment progress
                $progress->setProgress($progress->getProgress() + 1);

                // Persist changes
                $this->entityManager->persist($progress);

                // Check if the mission is completed
                if ($progress->getProgress() >= $competition->getGoal()) {
                    $progress->setIsCompleted(true);
                    $club->setPoints($club->getPoints() + $competition->getPoints());
                    $this->entityManager->persist($club);
                }
            }

        
        }


        // Flush changes to the database
        $this->entityManager->flush();

    }

}