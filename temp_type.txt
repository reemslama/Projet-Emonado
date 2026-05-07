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
    #[Assert\NotBlank(message: "Le libell├® est obligatoire")]
    private ?string $libelle = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

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
