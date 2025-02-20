<?php

namespace App\Entity;

use App\Repository\SondageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: SondageRepository::class)]
class Sondage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "The question cannot be empty.")]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "The question must contain at least {{ limit }} characters.",
        maxMessage: "The question cannot be longer than {{ limit }} characters."
    )]    
    private ?string $question = null;
    

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "sondages")]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Club::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Club $club; // Ajout de la relation avec le club
    
    
    #[ORM\OneToMany(targetEntity: ChoixSondage::class, mappedBy: "sondage", cascade: ["remove"], orphanRemoval: true)]
    #[Assert\Count(
        min: 2,
        minMessage: "A survey must have at least 2 choices."
    )]
private Collection $choix;

#[ORM\OneToMany(targetEntity: Commentaire::class, mappedBy: "sondage", cascade: ["remove"], orphanRemoval: true)]
private Collection $commentaires;

    #[ORM\OneToMany(targetEntity: Reponse::class, mappedBy: "sondage", cascade: ["persist", "remove"])]
    private Collection $reponses;

    

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->choix = new ArrayCollection();
        $this->reponses = new ArrayCollection();
        $this->commentaires = new ArrayCollection();    }



        public function addChoix(ChoixSondage $choix): self
        {
            if (!$this->choix->contains($choix)) {
                $this->choix->add($choix);
                $choix->setSondage($this); // Assure que le sondage est bien assigné au choix
            }
    
            return $this;
        }
    
        /* Suppression d'un choix
        public function removeChoix(ChoixSondage $choix): self
        {
            if ($this->choix->contains($choix)) {
                $this->choix->removeElement($choix);
        
                // Vérifie si le choix est bien associé à un sondage avant de déassocier
                if ($choix->getSondage() !== null) {
                    $choix->setSondage(null); // Déassocier le choix du sondage
                }
            }
        
            return $this;
        }    */
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

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

public function getUser(): User
{
    return $this->user;
}


// src/Entity/Sondage.php

public function getChoix(): Collection
{
    return $this->choix;
}
public function setClub(Club $club): self
{
    $this->club = $club;
    return $this;
}

public function getClub(): Club
{
    return $this->club;
}

public function getReponses(): Collection
{
    return $this->reponses;
}



}