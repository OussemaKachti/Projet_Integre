<?php

namespace App\Entity;

use App\Repository\CommentaireRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: CommentaireRepository::class)]
class Commentaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "The content of the comment cannot be empty.")]    
    private ?string $contenuComment = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateComment = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "commentaires")]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Sondage::class, inversedBy: "commentaires", cascade: ["persist"])]
#[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
private Sondage $sondage;

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getSondage(): Sondage
    {
        return $this->sondage;
    }

    public function setSondage(Sondage $sondage): self
    {
        $this->sondage = $sondage;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContenuComment(): ?string
    {
        return $this->contenuComment;
    }

    public function setContenuComment(string $contenuComment): static
    {
        $this->contenuComment = $contenuComment;

        return $this;
    }

    public function getDateComment(): ?\DateTimeInterface
    {
        return $this->dateComment;
    }

    public function setDateComment(\DateTimeInterface $dateComment): static
    {
        $this->dateComment = $dateComment;

        return $this;
    }

    
}