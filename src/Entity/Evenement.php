<?php

namespace App\Entity;

use App\Repository\EvenementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: EvenementRepository::class)]
class Evenement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nomEvent = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $descEvent = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    private ?string $imageEvent = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $startDate = null;
    #[ORM\Column(length: 255)]
private ?string $lieux = null;

    #[ORM\ManyToOne(targetEntity: Club::class, inversedBy: "evenements")]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private Club $club;

    #[ORM\ManyToOne(targetEntity: Categorie::class, inversedBy: "evenements")]
    #[ORM\JoinColumn(nullable: false)]
    private Categorie $categorie;

    #[ORM\OneToMany(targetEntity: Like::class, mappedBy: "evenement", cascade: ["persist", "remove"])]
    private Collection $likes;

    public function __construct()
    {
        $this->likes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomEvent(): ?string
    {
        return $this->nomEvent;
    }

    public function setNomEvent(string $nomEvent): static
    {
        $this->nomEvent = $nomEvent;

        return $this;
    }

    public function getDescEvent(): ?string
    {
        return $this->descEvent;
    }

    public function setDescEvent(string $descEvent): static
    {
        $this->descEvent = $descEvent;

        return $this;
    }

    public function getLieux(): ?string
{
    return $this->lieux;
}

public function setLieux(string $lieux): static
{
    $this->lieux = $lieux;
    return $this;
}

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getImageEvent(): ?string
    {
        return $this->imageEvent;
    }

    public function setImageEvent(string $imageEvent): static
    {
        $this->imageEvent = $imageEvent;

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
    public function getCategorie(): Categorie
    {
        return $this->categorie;
    }

    public function setCategorie(Categorie $categorie): self
    {
        $this->categorie = $categorie;
        return $this;
    }

    public function getClub(): Club
    {
        return $this->club;
    }

    public function setClub(Club $club): self
    {
        $this->club = $club;
        return $this;
    }

    public function getLikes(): Collection
    {
        return $this->likes;
    }
}