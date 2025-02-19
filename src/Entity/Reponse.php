<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Reponse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: "datetime")]
    private \DateTime $dateReponse;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: ChoixSondage::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ChoixSondage $choixSondage;

    #[ORM\ManyToOne(targetEntity: Sondage::class, inversedBy: "reponses")]
#[ORM\JoinColumn(nullable: false)]
private Sondage $sondage;

public function getSondage(): ?Sondage
{
    return $this->sondage;
}

public function setSondage(?Sondage $sondage): self
{
    $this->sondage = $sondage;
    return $this;
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

    public function getChoixSondage(): ChoixSondage
    {
        return $this->choixSondage;
    }

    public function setChoixSondage(ChoixSondage $choixSondage): self
    {
        $this->choixSondage = $choixSondage;
        return $this;
    }

    public function getDateReponse(): \DateTime
    {
        return $this->dateReponse;
    }

    public function setDateReponse(\DateTime $dateReponse): self
    {
        $this->dateReponse = $dateReponse;
        return $this;
    }
}