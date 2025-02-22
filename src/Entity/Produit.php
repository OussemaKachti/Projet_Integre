<?php

namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank (message:"name is required")]
    private ?string $nomProd = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank (message:"description is required")]
    #[Assert\Length(
        min: 8,
        max: 200,
        minMessage: 'Le titre doit faire au moins {{ limit }} caractères',
        maxMessage: 'Le titre ne doit pas faire plus de {{ limit }} caractères'
    )]
    private ?string $descProd = null;

    #[ORM\Column]
    #[Assert\NotBlank (message:"price is required")]
    #[Assert\Type(
        type: 'integer',
        message: 'The value {{ value }} is not a valid {{ type }}.',
    )]
    private ?float $prix = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank (message:"image is required")]
    #[Assert\Image(
        minWidth: 200,
        maxWidth: 400,
        minHeight: 200,
        maxHeight: 400,
    )]
    private ?string $imgProd = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\ManyToOne(targetEntity: Club::class, inversedBy: "produits")]
    #[ORM\JoinColumn(nullable: false,onDelete: "CASCADE")]
    #[Assert\NotBlank(message: 'Le nom du produit ne peut pas être vide')]
    private Club $club ;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank (message:"quantity is required")]
    #[Assert\Type(
        type: 'integer',
        message: 'The value {{ value }} is not a valid {{ type }}.',
    )]
    private ?string $quantity = null;

    public function getClub(): Club
    {
        return $this->club;
    }

    public function setClub(Club $club): self
    {
        $this->club = $club;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomProd(): ?string
    {
        return $this->nomProd;
    }

    public function setNomProd(string $nomProd): static
    {
        $this->nomProd = $nomProd;

        return $this;
    }

    public function getDescProd(): ?string
    {
        return $this->descProd;
    }

    public function setDescProd(string $descProd): static
    {
        $this->descProd = $descProd;

        return $this;
    }

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    public function getImgProd(): ?string
    {
        return $this->imgProd;
    }

    public function setImgProd(string $imgProd): static
    {
        $this->imgProd = $imgProd;

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

    public function getQuantity(): ?string
    {
        return $this->quantity;
    }

    public function setQuantity(?string $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }
}