<?php

namespace App\Entity;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\CompetitionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Enum\GoalTypeEnum;

#[ORM\Entity(repositoryClass: CompetitionRepository::class)]
class Competition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Competition name cannot be empty.")]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "Competition name must be at least {{ limit }} characters long.",
        maxMessage: "Competition name cannot be longer than {{ limit }} characters."
    )]
    private ?string $nomComp = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "Competition description cannot be empty.")]
    #[Assert\Length(
        min: 10,
        minMessage: "Description must be at least {{ limit }} characters long."
    )]
    private ?string $descComp = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull(message: "Start date is required.")]
    #[Assert\Type("\DateTimeInterface")]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull(message: "End date is required.")]
    #[Assert\Type("\DateTimeInterface")]
    #[Assert\GreaterThan(propertyPath: "startDate", message: "End date must be after the start date.")]
    
    private ?\DateTimeInterface $endDate = null;
    
    #[ORM\ManyToOne(targetEntity: Saison::class, inversedBy: "competitions")]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "A season must be selected.")]
    private ?Saison $saison = null;

    #[ORM\OneToMany(mappedBy: "competition", targetEntity: MissionProgress::class, cascade: ["persist", "remove"])]
    private Collection $missionProgresses;
    
    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotNull(message: "Points are required.")]
    #[Assert\PositiveOrZero(message: "Points must be a positive number or zero.")]
    private ?int $points = null;
    
    #[ORM\Column(type: "string", length: 20, options: ["default" => "pending"])]
    #[Assert\Choice(choices: ["pending", "activated", "expired"], message: "Invalid status.")] // ✅ Updated choices
    private string $status = "pending";


    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotNull(message: "Goal value is required.")]
    #[Assert\Positive(message: "Goal value must be greater than zero.")]
    private ?int $goal= null;

    #[ORM\Column(type: "string", enumType: GoalTypeEnum::class, length: 50)]
    #[Assert\Choice(callback: [GoalTypeEnum::class, 'cases'], message: "Invalid goal type.")]
    private ?GoalTypeEnum $goalType = GoalTypeEnum::EVENT_COUNT;


    


    public function __construct() {
        $this->missionProgresses = new ArrayCollection();
        $this->status = "pending"; // ✅ Ensure the default value is properly set
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomComp(): ?string
    {
        return $this->nomComp;
    }

    public function setNomComp(?string $nomComp): static
    {
        $this->nomComp = $nomComp;

        return $this;
    }

    public function getDescComp(): ?string
    {
        return $this->descComp;
    }

    public function setDescComp(?string $descComp): static
    {
        $this->descComp = $descComp;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }
    public function getSaison(): ?Saison 
    { 
        return $this->saison; 
    }
    public function setSaison(?Saison $saison): static 
    {
         $this->saison = $saison; return $this; 
    }
    public function getStatus(): ?string
{
    return $this->status;
}

    public function setStatus(string $status): static
{
    $this->status = $status;
    return $this;
}

    public function getMissionProgresses(): Collection 
    {
        return $this->missionProgresses;
    }
    public function addMissionProgress(MissionProgress $progress): static 
    {
        if (!$this->missionProgresses->contains($progress)) {
            $this->missionProgresses->add($progress);
            $progress->setCompetition($this);
        }
        return $this;
    }
    public function removeMissionProgress(MissionProgress $progress): static 
{
    if ($this->missionProgresses->removeElement($progress)) {
        if ($progress->getCompetition() === $this) { // Fix here
            $progress->setCompetition(null);
        }
    }
    return $this;
}

//*****************POINTS********************* */
public function getPoints(): ?int
{
    return $this->points;
}

public function setPoints(int $points): static
{
    $this->points = $points;
    return $this;
}

//*******************GOAlType******************* */
public function getGoalType(): ?GoalTypeEnum
{
    return $this->goalType;
}

public function setGoalType(GoalTypeEnum $goalType): static
{
    $this->goalType = $goalType;
    return $this;
}

//*******************GOAl******************* */


public function getGoal(): ?int
{
    return $this->goal;
}

public function setGoal(int $goal): static
{
    $this->goal = $goal;
    return $this;
}


//******************Méthode pour vérifier la progression des clubs*********voir combien de clubs ont complété la mission.************* */
public function getClubsCompleted(): int
{
    return $this->missionProgresses->filter(fn($mp) => $mp->getIsCompleted())->count();
}





}