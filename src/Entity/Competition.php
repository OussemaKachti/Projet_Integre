<?php

namespace App\Entity;

use App\Repository\CompetitionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: CompetitionRepository::class)]
class Competition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nomComp = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $descComp = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $endDate = null;
    
    #[ORM\ManyToMany(targetEntity: Club::class, inversedBy: "competitions")]
    private Collection $clubs;
    #[ORM\ManyToOne(targetEntity: Saison::class, inversedBy: "competitions")]
#[ORM\JoinColumn(nullable: false)]
private ?Saison $saison = null;

    
    public function __construct()
    {
        $this->clubs = new ArrayCollection();
    }

  

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomComp(): ?string
    {
        return $this->nomComp;
    }

    public function setNomComp(string $nomComp): static
    {
        $this->nomComp = $nomComp;

        return $this;
    }

    public function getDescComp(): ?string
    {
        return $this->descComp;
    }

    public function setDescComp(string $descComp): static
    {
        $this->descComp = $descComp;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }
}