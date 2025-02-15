<?php

namespace App\Entity;

use App\Repository\ClubRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use App\Enum\StatutClubEnum;


#[ORM\Entity(repositoryClass: ClubRepository::class)]
class Club
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nomC = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: "string", enumType: StatutClubEnum::class)]
    private StatutClubEnum $status;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $points = 0;

    #[ORM\OneToMany(targetEntity: Evenement::class, mappedBy: "club", cascade: ["persist", "remove"])]
    private Collection $evenements;

    #[ORM\OneToMany(targetEntity: Produit::class, mappedBy: "club", cascade: ["persist", "remove"])]
    private Collection $produits;

    #[ORM\OneToMany(targetEntity: ParticipationMembre::class, mappedBy: "club", cascade: ["persist", "remove"])]
    private Collection $participations;


    #[ORM\ManyToOne(targetEntity: User::class)]
#[ORM\JoinColumn(nullable: false)]
private ?User $president = null;



    public function __construct()
    {
        $this->evenements = new ArrayCollection();
        $this->produits = new ArrayCollection();
        $this->participations = new ArrayCollection();


    }

    public function getEvenements(): Collection
    {
        return $this->evenements;
        
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomC(): ?string
    {
        return $this->nomC;
    }

    public function setNomC(string $nomC): static
    {
        $this->nomC = $nomC;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStatus(): StatutClubEnum
    {
        return $this->status;
    }

    public function setStatus(StatutClubEnum $status): self
    {
        $this->status = $status;
        return $this;
    }   
    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function getPoints(): int
    {
        return $this->points;
    }

    public function setPoints(int $points): static
    {
        $this->points = $points;
        return $this;
    }

    public function addPoints(int $points): static
    {
        $this->points += $points;
        return $this;
    }
    public function getProduits(): Collection
    {
        return $this->produits;
    }

    public function getParticipations(): Collection
    {
        return $this->participations;
    }


    public function getPresident(): ?User
{
    return $this->president;
}

public function setPresident(User $president): static
{
    $this->president = $president;
    return $this;
}
}