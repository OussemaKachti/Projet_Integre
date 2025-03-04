<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\MissionProgressRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\EntityManagerInterface;

#[ORM\Entity(repositoryClass: MissionProgressRepository::class)]
class MissionProgress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Club::class, inversedBy: "missionProgresses")]
    #[ORM\JoinColumn(nullable: false)]
    private ?Club $club = null;

    #[ORM\ManyToOne(targetEntity: Competition::class, inversedBy: "missionProgresses")]
    #[ORM\JoinColumn(nullable: false)]
    private ?Competition $competition = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotNull(message: "Progress must be set.")]
    #[Assert\PositiveOrZero(message: "Progress must be zero or a positive number.")]
    private int $progress = 0;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isCompleted = false; // To track mission completion

    public function __construct() {
        $this->progress = 0;
        $this->isCompleted = false;
    }
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClub(): ?Club
    {
        return $this->club;
    }

    public function setClub(?Club $club): static
    {
        $this->club = $club;
        return $this;
    }

    public function getCompetition(): ?Competition
    {
        return $this->competition;
    }

    public function setCompetition(?Competition $competition): static
    {
        $this->competition = $competition;
        return $this;
    }

    public function getProgress(): ?int
    {
        return $this->progress;
    }

    public function setProgress(int $progress, EntityManagerInterface $entityManager): static
    {
        $this->progress = $progress;
        $this->checkCompletion($entityManager);
        return $this;
    }

    public function getIsCompleted(): bool
    {
        return $this->isCompleted;
    }

    public function setIsCompleted(bool $isCompleted): static
    {
        $this->isCompleted = $isCompleted;
        return $this;
    }

    public function checkCompletion(EntityManagerInterface $entityManager): void
    {
        if (!$this->competition || !$this->club) {
            return;
        }

        switch ($this->competition->getGoalType()) {
            case GoalTypeEnum::EVENT_COUNT:
            case GoalTypeEnum::EVENT_LIKES:
            case GoalTypeEnum::MEMBER_COUNT:
                if ($this->progress >= $this->competition->getGoal() && !$this->isCompleted) {
                    $this->isCompleted = true;
                    $this->awardPointsToClub($entityManager);
                }
                break;
            default:
                $this->isCompleted = false;
        }
    }

    private function awardPointsToClub(EntityManagerInterface $entityManager): void
    {
        if (!$this->competition) {
            return; // ✅ Prevent error if competition is null
        }

        $points = $this->competition->getPoints();
        if ($points > 0) {
            $this->club->setPoints($this->club->getPoints() + $points);

            // ✅ Persist changes to the database
            $entityManager->persist($this);
            $entityManager->persist($this->club);
            $entityManager->flush();
        }
    }
}
