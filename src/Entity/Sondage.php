<?php

namespace App\Entity;

use App\Repository\SondageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;


#[ORM\Entity(repositoryClass: SondageRepository::class)]
class Sondage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $question = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "sondages")]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;
    
    
    #[ORM\OneToMany(targetEntity: ChoixSondage::class, mappedBy: "sondage", cascade: ["persist", "remove"])]
    private Collection $choix;

    #[ORM\OneToMany(targetEntity: Reponse::class, mappedBy: "sondage", cascade: ["persist", "remove"])]
    private Collection $reponses;

    #[ORM\OneToMany(targetEntity: Commentaire::class, mappedBy: "sondage", cascade: ["persist", "remove"])]
    private Collection $commentaires;

    public function __construct()
    {
        $this->choix = new ArrayCollection();
        $this->reponses = new ArrayCollection();
        $this->commentaires = new ArrayCollection();    }

    public function getCommentaires(): Collection
    {
        return $this->commentaires;
    }
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuestion(): ?string
    {
        return $this->question;
    }

    public function setQuestion(string $question): static
    {
        $this->question = $question;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function setUser(User $user): self
{
    $this->user = $user;
    return $this;
}
}