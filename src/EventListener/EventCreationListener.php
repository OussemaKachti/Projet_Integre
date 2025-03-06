<?php
// src/EventListener/EventCreationListener.php

namespace App\EventListener;
use Doctrine\ORM\Event\PostPersist;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Doctrine\ORM\Event\LifecycleEventArgs;
use App\Entity\Evenement;
use App\Repository\MissionProgressRepository;
use App\Repository\CompetitionRepository;
use App\Service\MissionCompletionChecker;
use Doctrine\ORM\EntityManagerInterface;
use App\Enum\GoalTypeEnum;
use Psr\Log\LoggerInterface;

#[AsEventListener(event: 'postPersist', method: 'postPersist')]
class EventCreationListener
{
    private MissionProgressRepository $progressRepository;
    private CompetitionRepository $competitionRepository;
    private EntityManagerInterface $entityManager;
    private MissionCompletionChecker $completionChecker;
    private LoggerInterface $logger;

    public function __construct(
        MissionProgressRepository $progressRepository,
        CompetitionRepository $competitionRepository,
        EntityManagerInterface $entityManager,
        MissionCompletionChecker $completionChecker,
        LoggerInterface $logger
    ) {
        $this->progressRepository = $progressRepository;
        $this->competitionRepository = $competitionRepository;
        $this->entityManager = $entityManager;
        $this->completionChecker = $completionChecker;
        $this->logger = $logger;
    }

    public function postPersist(Evenement $event): void
    {
        
        // Log the event for debugging
        $this->logger->info('PostPersist event triggered for class: ' . get_class($event));

        // Only proceed if the entity is an Evenement
        if (!$event instanceof Evenement) {
            return;
        }

        $this->logger->info('Processing event: ' . $event->getNomEvent());
        
        $club = $event->getClub();

        if (!$club) {
            $this->logger->warning('Event has no club associated');
            return; // No club, nothing to update
        }

        // Find all activated competitions for this club with EVENT_COUNT goal type
        $competitions = $this->competitionRepository->findBy([
            'status' => 'activated',
            'goalType' => GoalTypeEnum::EVENT_COUNT,
        ]);

        $this->logger->info('Found ' . count($competitions) . ' active competitions for event count');

        $updated = false;
        foreach ($competitions as $competition) {
            // Find the MissionProgress for this club and competition
            $progress = $this->progressRepository->findOneBy([
                'club' => $club,
                'competition' => $competition,
                'isCompleted' => false,
            ]);

            if ($progress) {
                // Increment progress
                $currentProgress = $progress->getProgress();
                $progress->setProgress($currentProgress + 1);
                $this->entityManager->persist($progress);
                $updated = true;
                
                $this->logger->info(sprintf(
                    'Updated progress for club %s in competition %s: %d/%d',
                    $club->getNomC(),
                    $competition->getNomComp(),
                    $progress->getProgress(),
                    $competition->getGoal()
                ));
                
                // Use the MissionCompletionChecker to check if the mission is completed
                $this->completionChecker->checkSingleProgress($progress);
            } else {
                $this->logger->warning(sprintf(
                    'No progress entry found for club %s in competition %s',
                    $club->getNomC(),
                    $competition->getNomComp()
                ));
            }
        }

        // Flush if we made progress updates
        if ($updated) {
            $this->entityManager->flush();
        }
    }
}