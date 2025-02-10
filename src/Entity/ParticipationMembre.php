<?php

namespace App\Entity;

use App\Repository\ParticipationMembreRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParticipationMembreRepository::class)]
class ParticipationMembre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateRequest = null;

    #[ORM\Column(type: "string", length: 20)]
    private string $statut = "enAttente"; // Valeurs possibles : enAttente, acceptée, refusée

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "participations")]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Club::class, inversedBy: "participations")]
    #[ORM\JoinColumn(nullable: false)]
    private Club $club;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateRequest(): ?\DateTimeInterface
    {
        return $this->dateRequest;
    }

    public function setDateRequest(\DateTimeInterface $dateRequest): static
    {
        $this->dateRequest = $dateRequest;

        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }
}