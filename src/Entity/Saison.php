<?php

namespace App\Entity;

use App\Repository\SaisonRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;


#[ORM\Entity(repositoryClass: SaisonRepository::class)]
#[Vich\Uploadable] // Enables VichUploader for this entity
class Saison
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[Vich\UploadableField(mapping: "saison", fileNameProperty: "image")]
    private ?File $imageFile = null;


    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Season name cannot be empty.")]
    #[Assert\Length(
        min: 3,
        max: 50,
        minMessage: "Season name must be at least {{ limit }} characters long.",
        maxMessage: "Season name cannot be longer than {{ limit }} characters."
    )]
    private ?string $nomSaison = null;
    
    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\NotBlank(message: "Description cannot be empty.")]
    #[Assert\Length(
        min: 10,
        minMessage: "Description must be at least {{ limit }} characters long."
    )]
    private ?string $descSaison = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Assert\NotBlank(message: "End date is required.")]
    #[Assert\GreaterThan('today', message: "End date must be in the future.")]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\OneToMany(mappedBy: "saison", targetEntity: Competition::class, cascade: ["persist", "remove"], orphanRemoval: true)]
    private Collection $competitions;
    
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;
        
    public function __construct()
    {
        $this->competitions = new ArrayCollection();
    }

    public function getCompetitions(): Collection
    {
        return $this->competitions;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomSaison(): ?string
    {
        return $this->nomSaison;
    }

    public function setNomSaison(?string $nomSaison): static
    {
        $this->nomSaison = $nomSaison;

        return $this;
    }

    public function getDescSaison(): ?string
    {
        return $this->descSaison;
    }

    public function setDescSaison(?string $descSaison): static
    {
        $this->descSaison = $descSaison;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getImage(): ?string { return $this->image; }
    
    public function setImage(?string $image): self 
        {
            $this->image = $image; 
            return $this; 
        }
    
    public function setImageFile(?File $imageFile = null): void 
    {
        $this->imageFile = $imageFile;
        if ($imageFile) 
        {
            $this->updatedAt = new \DateTimeImmutable(); 
        }
    }

    public function getImageFile(): ?File 
    { 
        return $this->imageFile; 
    }
   

public function getUpdatedAt(): ?\DateTimeImmutable
{
    return $this->updatedAt;
}



    public function addCompetition(Competition $competition): static 
    {
        if (!$this->competitions->contains($competition)) {
            $this->competitions->add($competition);
            $competition->setSeason($this);
        }
        return $this;
    }

    public function removeCompetition(Competition $competition): static 
    {
        if ($this->competitions->removeElement($competition)) {
            if ($competition->getSeason() === $this) {
                $competition->setSeason(null);
            }
        }
        return $this;
    }
}