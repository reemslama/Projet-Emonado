<?php

namespace App\Entity;

use App\Repository\TypeRendezVousRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TypeRendezVousRepository::class)]
class TypeRendezVous
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le libellé est obligatoire")]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: "Le libellé doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le libellé ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $libelle = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(
        max: 500,
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $description = null;

    // ✅ AJOUT : Champ couleur pour le calendrier
    #[ORM\Column(length: 7, nullable: true)]
    private ?string $couleur = '#0d6efd';

    #[ORM\OneToMany(mappedBy: 'type', targetEntity: RendezVous::class)]
    private Collection $rendezVouses;

    public function __construct()
    {
        $this->rendezVouses = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->libelle ?? '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): self
    {
        $this->libelle = $libelle;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    // ✅ AJOUT : Getter pour couleur
    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    // ✅ AJOUT : Setter pour couleur
    public function setCouleur(?string $couleur): self
    {
        $this->couleur = $couleur;
        return $this;
    }

    /**
     * @return Collection<int, RendezVous>
     */
    public function getRendezVouses(): Collection
    {
        return $this->rendezVouses;
    }

    public function addRendezVous(RendezVous $rendezVous): self
    {
        if (!$this->rendezVouses->contains($rendezVous)) {
            $this->rendezVouses->add($rendezVous);
            $rendezVous->setType($this);
        }
        return $this;
    }

    public function removeRendezVous(RendezVous $rendezVous): self
    {
        if ($this->rendezVouses->removeElement($rendezVous)) {
            if ($rendezVous->getType() === $this) {
                $rendezVous->setType(null);
            }
        }
        return $this;
    }
}