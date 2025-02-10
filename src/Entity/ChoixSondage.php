<?php

namespace App\Entity;

use App\Repository\ChoixSondageRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;


#[ORM\Entity(repositoryClass: ChoixSondageRepository::class)]
class ChoixSondage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $contenu = null;

    #[ORM\ManyToOne(targetEntity: Sondage::class, inversedBy: "choix")]
    #[ORM\JoinColumn(nullable: false)]
    private Sondage $sondage;

    #[ORM\OneToMany(targetEntity: Reponse::class, mappedBy: "choixSondage", cascade: ["persist", "remove"])]
    private Collection $reponses;

    public function __construct()
    {
        $this->reponses = new ArrayCollection();
    }

    public function getReponses(): Collection
    {
        return $this->reponses;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;

        return $this;
    }

    
}