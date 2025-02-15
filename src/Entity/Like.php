<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "likes")]
class Like
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "likes")]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Evenement::class, inversedBy: "likes")]
    #[ORM\JoinColumn(nullable: false)]
    private Evenement $evenement;

    #[ORM\Column(type: "datetime")]
    private \DateTime $dateLike;

    public function __construct()
    {
        $this->dateLike = new \DateTime();
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getEvenement(): Evenement
    {
        return $this->evenement;
    }

    public function setEvenement(Evenement $evenement): self
    {
        $this->evenement = $evenement;
        return $this;
    }

    public function getDateLike(): \DateTime
    {
        return $this->dateLike;
    }

    public function setDateLike(\DateTime $dateLike): self
    {
        $this->dateLike = $dateLike;
        return $this;
    }
}