<?php

namespace App\Entity;

use App\Repository\ParticipationEventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ParticipationEventRepository::class)]
class ParticipationEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "participations", cascade: ["remove"])]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Evenement::class, inversedBy: "participations", cascade: ["remove"])]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?Evenement $evenement = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull(message: "La date de participation est obligatoire.")]
    private ?\DateTimeInterface $dateparticipation = null;

    public function __construct()
    {
        $this->dateparticipation = new \DateTime(); // Définit automatiquement la date actuelle
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getEvenement(): ?Evenement
    {
        return $this->evenement;
    }

    public function setEvenement(?Evenement $evenement): static
    {
        $this->evenement = $evenement;
        return $this;
    }

    public function getDateparticipation(): ?\DateTimeInterface
    {
        return $this->dateparticipation;
    }

    public function setDateparticipation(\DateTimeInterface $dateparticipation): static
    {
        $this->dateparticipation = $dateparticipation;
        return $this;
    }
}
