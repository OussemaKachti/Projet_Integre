<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use App\Entity\Evenement;
use App\Repository\MissionProgressRepository;
use App\Service\MissionCompletionChecker;
use Doctrine\ORM\EntityManagerInterface;

class EventCreationListener
{
    private MissionProgressRepository $progressRepository;
    private MissionCompletionChecker $missionChecker;
    private EntityManagerInterface $entityManager;

    public function __construct(
        MissionProgressRepository $progressRepository,
        MissionCompletionChecker $missionChecker,
        EntityManagerInterface $entityManager
    ) {
        $this->progressRepository = $progressRepository;
        $this->missionChecker = $missionChecker;
        $this->entityManager = $entityManager;
    }

    public function onKernelTerminate(TerminateEvent $event)
    {
        // Get the newly created event from the request
        $request = $event->getRequest();
        $eventEntity = $request->attributes->get('event');

        if (!$eventEntity instanceof Event) {
            return; // Not an event, ignore
        }

        $club = $eventEntity->getClub(); // Assuming the Event entity has a club relation

        // Get the active mission progress for this club
        $progress = $this->progressRepository->findOneBy([
            'club' => $club,
            'isCompleted' => false
        ]);

        if ($progress) {
            // Increase progress by 1
            $progress->setProgress($progress->getProgress() + 1);

            // Persist update
            $this->entityManager->persist($progress);
            $this->entityManager->flush();

            // Check if mission is completed
            $this->missionChecker->checkAndUpdateMissionStatus();
        }
    }
}
