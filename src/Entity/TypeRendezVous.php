<?php

namespace App\Entity;

use App\Repository\TypeRendezVousRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\RendezVous;

#[ORM\Entity(repositoryClass: TypeRendezVousRepository::class)]
class TypeRendezVous
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\OneToMany(mappedBy: 'type', targetEntity: RendezVous::class)]
    private Collection $rendezVous;

    public function __construct()
    {
        $this->rendezVous = new ArrayCollection();
    }

    /**
     * Tâche 2 : Méthode magique pour transformer l'objet en texte.
     * Indispensable pour l'affichage dans les vues Twig et les EntityType Symfony.
     */
    public function __toString(): string
    {
        return $this->description ?? 'Type non défini';
    }

    public function getId(): ?int
    {
        return $this->id;
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
    public function getRendezVous(): Collection
    {
        return $this->rendezVous;
    }

    public function addRendezVous(RendezVous $rendezVous): self
    {
        if (!$this->rendezVous->contains($rendezVous)) {
            $this->rendezVous->add($rendezVous);
            $rendezVous->setType($this);
        }
        return $this;
    }

    public function removeRendezVous(RendezVous $rendezVous): self
    {
        if ($this->rendezVous->removeElement($rendezVous)) {
            if ($rendezVous->getType() === $this) {
                $rendezVous->setType(null);
            }
        }
        return $this;
    }
}