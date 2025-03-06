<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use App\Service\MissionCompletionChecker;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Psr\Log\LoggerInterface;


class MissionProgressListener implements EventSubscriberInterface
{
    private MissionCompletionChecker $missionCompletionChecker;
    private LoggerInterface $logger;

    public function __construct(MissionCompletionChecker $missionCompletionChecker, LoggerInterface $logger)
    {
        $this->missionCompletionChecker = $missionCompletionChecker;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TerminateEvent::class => 'onKernelTerminate',
        ];
    }

    public function onKernelTerminate(TerminateEvent $event)
    {
        // Run mission completion check
        $this->logger->info('Checking mission progress after request.');
        $this->missionCompletionChecker->checkAndUpdateMissionStatus();
    }
}
