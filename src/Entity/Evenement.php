<?php 
// src/Entity/Evenement.php

namespace App\Entity;

use App\Repository\EvenementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: EvenementRepository::class)]
class Evenement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom de l'événement ne peut pas être vide.")]
    #[Assert\Regex(
        pattern: "/^(?!\d+$).+$/",
        message: "Le nom de l'événement ne peut pas être uniquement un numéro."
    )]
    private ?string $nomEvent = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "La description de l'événement ne peut être vide.")]
    private ?string $descEvent = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le type de l'événement est obligatoire.")]
    #[Assert\Choice(choices: ['open', 'closed'], message: "Choisissez un type d'événement valide : open ou closed.")]
    private ?string $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageDescription = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull(message: "La date de début est obligatoire.")]
    #[Assert\GreaterThanOrEqual("today", message: "La date de début ne peut être dans le passé.")]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le lieu de l'événement est obligatoire.")]
    private ?string $lieux = null;

    #[ORM\ManyToOne(targetEntity: Club::class, inversedBy: "evenements")]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    #[Assert\NotNull(message: "Le club est obligatoire.")]
    private Club $club;

    #[ORM\ManyToOne(targetEntity: Categorie::class, inversedBy: "evenements")]
    #[ORM\JoinColumn(nullable: true)]
    private ?Categorie $categorie = null;

    #[ORM\OneToMany(targetEntity: Like::class, mappedBy: "evenement", cascade: ["persist", "remove"])]
    private Collection $likes;

    #[ORM\OneToMany(targetEntity: ParticipationEvent::class, mappedBy: "evenement", cascade: ["persist", "remove"])]
    private Collection $participations;

    public function __construct()
    {
        $this->likes = new ArrayCollection();
        $this->participations = new ArrayCollection();
    }

    // Getters et setters

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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getImageDescription(): ?string
    {
        return $this->imageDescription;
    }

    public function setImageDescription(?string $imageDescription): self
    {
        $this->imageDescription = $imageDescription;
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

    public function setEndDate(?\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;
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

    public function getClub(): Club
    {
        return $this->club;
    }

    public function setClub(Club $club): self
    {
        $this->club = $club;
        return $this;
    }

    public function getCategorie(): ?Categorie
    {
        return $this->categorie;
    }

    public function setCategorie(?Categorie $categorie): self
    {
        $this->categorie = $categorie;
        return $this;
    }

    public function getLikes(): Collection
    {
        return $this->likes;
    }

    /**
     * @return Collection<int, ParticipationEvent>
     */
    public function getParticipations(): Collection
    {
        return $this->participations;
    }

    public function addParticipation(ParticipationEvent $participation): static
    {
        if (!$this->participations->contains($participation)) {
            $this->participations->add($participation);
            $participation->setEvenement($this);
        }

        return $this;
    }

    public function removeParticipation(ParticipationEvent $participation): static
    {
        if ($this->participations->removeElement($participation)) {
            // set the owning side to null (unless already changed)
            if ($participation->getEvenement() === $this) {
                $participation->setEvenement(null);
            }
        }

        return $this;
    }

    /**
     * Check if a user is participating in this event
     */
    public function isUserParticipating(User $user): bool
    {
        foreach ($this->participations as $participation) {
            if ($participation->getUser() === $user) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get the number of participants
     */
    public function getParticipantsCount(): int
    {
        return $this->participations->count();
    }

    // Méthode de validation pour comparer startDate et endDate
    #[Assert\Callback]
    public function validateDates(ExecutionContextInterface $context, $payload)
    {
        if ($this->endDate !== null && $this->startDate !== null && $this->endDate <= $this->startDate) {
            $context->buildViolation("La date de fin doit être postérieure à la date de début.")
                ->atPath('endDate')
                ->addViolation();
        }
    }
}