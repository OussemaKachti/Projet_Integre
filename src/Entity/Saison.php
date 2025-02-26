<?php

namespace App\Entity;

use App\Repository\SaisonRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;



#[ORM\Entity(repositoryClass: SaisonRepository::class)]
class Saison
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Season name cannot be empty.")]
    #[Assert\Length(
        min: 3,
        max: 50,
        minMessage: "Season name must be at least {{ limit }} characters long.",
        maxMessage: "Season name cannot be longer than {{ limit }} characters."
    )]
    private ?string $nomSaison = null;
    
    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\NotBlank(message: "Description cannot be empty.")]
    #[Assert\Length(
        min: 10,
        minMessage: "Description must be at least {{ limit }} characters long."
    )]
    private ?string $descSaison = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Assert\NotBlank(message: "End date is required.")]
    #[Assert\GreaterThan('today', message: "End date must be in the future.")]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\OneToMany(mappedBy: "saison", targetEntity: Competition::class, cascade: ["persist", "remove"], orphanRemoval: true)]
    private Collection $competitions;


    public function __construct()
    {
        $this->competitions = new ArrayCollection();
    }

    public function getCompetitions(): Collection
    {
        return $this->competitions;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomSaison(): ?string
    {
        return $this->nomSaison;
    }

    public function setNomSaison(?string $nomSaison): static
    {
        $this->nomSaison = $nomSaison;

        return $this;
    }

    public function getDescSaison(): ?string
    {
        return $this->descSaison;
    }

    public function setDescSaison(?string $descSaison): static
    {
        $this->descSaison = $descSaison;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;
        return $this;
    }



    public function addCompetition(Competition $competition): static 
    {
        if (!$this->competitions->contains($competition)) {
            $this->competitions->add($competition);
            $competition->setSeason($this);
        }
        return $this;
    }

    public function removeCompetition(Competition $competition): static 
    {
        if ($this->competitions->removeElement($competition)) {
            if ($competition->getSeason() === $this) {
                $competition->setSeason(null);
            }
        }
        return $this;
    }
}