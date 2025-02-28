<?php

namespace App\Entity;

use App\Repository\UserRepository;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Enum\RoleEnum;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;





#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    //control saisis pour nopn , prenom
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Last name cannot be empty')]
    #[Assert\Length(max: 255, maxMessage: 'Last name cannot exceed 255 characters')]
    private ?string $nom = null;


    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'First name cannot be empty')]
    #[Assert\Length(max: 255, maxMessage: 'First name cannot exceed 255 characters')]
    private ?string $prenom = null;

    //control saisis mail
    #[Assert\Email(
        message: 'The email "{{ value }}" is not a valid email address.',
        mode: 'strict' // Enforces stricter validation (e.g., checks for valid domain names)
    )]
    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null;

    //control saisis pass
    #[Assert\NotBlank(message: 'Password cannot be empty')]
    #[ORM\Column(length: 255)]
    #[Assert\Regex(
        pattern: '/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/',
        message: 'Password must include uppercase, lowercase, numbers, and special characters'
    )]
    private ?string $password = null;

    #[ORM\Column(type: "string", enumType: RoleEnum::class)]
    private RoleEnum $role;

    //control saisis num
    #[ORM\Column(length: 15, nullable: true)]
    #[Assert\NotBlank(message: 'Phone number cannot be empty')]
    // #[Assert\Length(min: 8, max: 15, minMessage: 'Phone number must be at least 8 digits', maxMessage: 'Phone number cannot exceed 15 digits')]
    #[Assert\Regex(
        pattern: '/^((\+|00)216)?([2579][0-9]{7}|(3[012]|4[01]|8[0128])[0-9]{6}|42[16][0-9]{5})$/',
        message: 'Invalid phone number'
    )]
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
        $this->nom = $nom ;

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

    public function setPassword(string $password): self
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
    public function getFullName()
    {
        return $this->nom . ' ' . $this->prenom;
    }



    public function getRoles(): array
    {
        // Ensure the role is always an array and includes ROLE_ prefix
        return ['ROLE_' . strtoupper($this->role->value)];
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * Check if user has a specific role
     * @param string $role
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles(), true);
    }

    /**
     * Check if user is admin
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role === RoleEnum::ADMINISTRATEUR;
    }
    public function __toString(): string
{
    return $this->getFullName();
}
}