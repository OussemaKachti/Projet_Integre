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

    #[ORM\ManyToOne(targetEntity: ChoixSondage::class, inversedBy: "reponses")]
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

    
    public function getPollResults(Sondage $sondage): array
    {
        $totalVotes = count($sondage->getReponses()); // Remplacement de getVotes() par getReponses()
        $results = [];
    
        foreach ($sondage->getChoix() as $choix) {
            // Filtrer les réponses correspondant à ce choix
            $choixVotes = count(array_filter($sondage->getReponses()->toArray(), function ($reponse) use ($choix) {
                return $reponse->getChoix() === $choix;
            }));
    
            $percentage = $totalVotes > 0 ? ($choixVotes / $totalVotes) * 100 : 0;
    
            // Déterminer la couleur en fonction du pourcentage
            $color = $this->getColorByPercentage($percentage);
    
            $results[] = [
                'choix' => $choix->getContenu(),
                'percentage' => round($percentage, 2),
                'color' => $color
            ];
        }
    
        return $results;
    }
    
    private function getColorByPercentage(float $percentage): string
    {
        if ($percentage <= 20) {
            return '#e74c3c'; // Rouge
        } elseif ($percentage <= 40) {
            return '#f39c12'; // Orange
        } elseif ($percentage <= 60) {
            return '#f1c40f'; // Jaune
        } elseif ($percentage <= 80) {
            return '#2ecc71'; // Vert
        } else {
            return '#3498db'; // Bleu
        }
    }
}