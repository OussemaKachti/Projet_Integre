<?php

namespace App\Entity;

use App\Repository\SaisonRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;



#[ORM\Entity(repositoryClass: SaisonRepository::class)]
class Saison
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nomSaison = null;

    #[ORM\OneToMany(targetEntity: Competition::class, mappedBy: "saison", cascade: ["persist", "remove"])]
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

    public function setNomSaison(string $nomSaison): static
    {
        $this->nomSaison = $nomSaison;

        return $this;
    }
}