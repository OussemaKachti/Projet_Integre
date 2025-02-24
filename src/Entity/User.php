<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Enum\RoleEnum;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: UserRepository::class)]
class User 
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[Assert\Email]
    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null;
    
    #[Assert\NotBlank]
    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(type: "string", enumType: RoleEnum::class)]
    private RoleEnum $role;

    #[Assert\NotBlank]
#[Assert\Length(min: 8, max: 15)]
#[ORM\Column(length: 15, nullable: true)]
private ?string $tel = null;
    
    #[ORM\OneToMany(targetEntity: Sondage::class, mappedBy: "user", cascade: ["persist", "remove"])]
    private Collection $sondages;

    #[ORM\OneToMany(targetEntity: ParticipationMembre::class, mappedBy: "user", cascade: ["persist", "remove"])]
    private Collection $participations;

    #[ORM\OneToMany(targetEntity: Commentaire::class, mappedBy: "user", cascade: ["persist", "remove"])]
    private Collection $commentaires;

    #[ORM\OneToMany(targetEntity: Like::class, mappedBy: "user", cascade: ["persist", "remove"])]
    private Collection $likes;

    
    public function __construct()
    {
        $this->participations = new ArrayCollection();
        $this->commentaires = new ArrayCollection();
        $this->likes = new ArrayCollection();
        $this->sondages = new ArrayCollection();
        $this->commandes = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getTel(): ?string
{
    return $this->tel;
}

public function setTel(string $tel): static
{
    $this->tel = $tel;
    return $this;
}

    public function getRole(): RoleEnum
    {
        return $this->role;
    }

    public function setRole(RoleEnum $role): self
    {
        $this->role = $role;
        return $this;
    }
    
    public function getParticipations(): Collection
    {
        return $this->participations;
    }

    public function getCommentaires(): Collection
    {
        return $this->commentaires;
    }

    public function getLikes(): Collection
    {
        return $this->likes;
    }
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Commande::class)]
    private Collection $commandes;

    

    /**
     * @return Collection|Commande[]
     */
    public function getCommandes(): Collection
    {
        return $this->commandes;
    }

    public function addCommande(Commande $commande): self
    {
        if (!$this->commandes->contains($commande)) {
            $this->commandes[] = $commande;
            $commande->setUser($this); // Lien inverse
        }

        return $this;
    }

    public function removeCommande(Commande $commande): self
    {
        if ($this->commandes->removeElement($commande)) {
            // Lien inverse
            if ($commande->getUser() === $this) {
                $commande->setUser(null);
            }
        }

        return $this;
    }
}