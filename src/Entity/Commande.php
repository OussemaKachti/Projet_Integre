<?php

namespace App\Entity;

use App\Repository\CommandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\StatutCommandeEnum;


#[ORM\Entity(repositoryClass: CommandeRepository::class)]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: "string", enumType: StatutCommandeEnum::class)]
    private StatutCommandeEnum $statut;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateComm = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'commandes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null; 

    /**
     * @var Collection<int, Orderdetails>
     */
    #[ORM\OneToMany(targetEntity: Orderdetails::class, mappedBy: 'commande',cascade: ['persist', 'remove'])]
    private Collection $orderDetails;

    public function __construct()
    {
        $this->orderDetails = new ArrayCollection(); 
        $this->produit = new ArrayCollection();
    }
    public function getTotal(): float
    {
        $total = 0.0;

        foreach ($this->orderDetails as $orderDetail) {
            $total += $orderDetail->getTotal() ?? 0;
        }

        return $total;
    }
    public function setTotal(float $total): self
    {
        $this->total = $total;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatut(): StatutCommandeEnum
    {
        return $this->statut;
    }

    public function setStatut(StatutCommandeEnum $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getDateComm(): ?\DateTimeInterface
    {
        return $this->dateComm;
    }

    public function setDateComm(\DateTimeInterface $dateComm): static
    {
        $this->dateComm = $dateComm;

        return $this;
    }

    
    public function addProduit(Orderdetails $produit): static
    {
        if (!$this->produit->contains($produit)) {
            $this->produit->add($produit);
            $produit->setCommande($this);
        }

        return $this;
    }

    public function removeProduit(Orderdetails $produit): static
    {
        if ($this->produit->removeElement($produit)) {
            // set the owning side to null (unless already changed)
            if ($produit->getCommande() === $this) {
                $produit->setCommande(null);
            }
        }

        return $this;
    }
    /**
     * @return Collection<int, OrderDetails>
     */
    public function getOrderDetails(): Collection
    {
        return $this->orderDetails;
    }

    public function addOrderDetails(OrderDetails $orderDetails): self
    {
        if (!$this->orderDetails->contains($orderDetails)) {
            $this->orderDetails[] = $orderDetails;
            $orderDetails->setCommande($this); // Associer à la commande
        }

        return $this;
    }

    public function removeOrderDetails(OrderDetails $orderDetails): self
    {
        if ($this->orderDetails->removeElement($orderDetails)) {
            // Déassocier l'élément supprimé
            if ($orderDetails->getCommande() === $this) {
                $orderDetails->setCommande(null);
            }
        }

        return $this;
    }
    

    public function getUser(): ?User
    {
        return $this->user;
    }
    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

}