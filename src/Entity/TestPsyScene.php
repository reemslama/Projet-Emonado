<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: 'test_psy_scene')]
class TestPsyScene
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $numero = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $titre = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $titreEn = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $titreAr = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imagePath = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $soundPath = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $descriptionPsy = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $descriptionEn = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $descriptionAr = null;

    #[ORM\Column(type: 'boolean')]
    private bool $actif = true;

    #[ORM\OneToMany(mappedBy: 'scene', targetEntity: TestPsyReponse::class, cascade: ['persist', 'remove'])]
    private Collection $reponses;

    public function __construct()
    {
        $this->reponses = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getNumero(): ?int { return $this->numero; }
    public function setNumero(int $numero): self { $this->numero = $numero; return $this; }
    public function getType(): ?string { return $this->type; }
    public function setType(string $type): self { $this->type = $type; return $this; }
    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(?string $titre): self { $this->titre = $titre; return $this; }
    public function getTitreEn(): ?string { return $this->titreEn; }
    public function setTitreEn(?string $titreEn): self { $this->titreEn = $titreEn; return $this; }
    public function getTitreAr(): ?string { return $this->titreAr; }
    public function setTitreAr(?string $titreAr): self { $this->titreAr = $titreAr; return $this; }
    public function getImagePath(): ?string { return $this->imagePath; }
    public function setImagePath(?string $imagePath): self { $this->imagePath = $imagePath; return $this; }
    public function getSoundPath(): ?string { return $this->soundPath; }
    public function setSoundPath(?string $soundPath): self { $this->soundPath = $soundPath; return $this; }
    public function getDescriptionPsy(): ?string { return $this->descriptionPsy; }
    public function setDescriptionPsy(?string $descriptionPsy): self { $this->descriptionPsy = $descriptionPsy; return $this; }
    public function getDescriptionEn(): ?string { return $this->descriptionEn; }
    public function setDescriptionEn(?string $descriptionEn): self { $this->descriptionEn = $descriptionEn; return $this; }
    public function getDescriptionAr(): ?string { return $this->descriptionAr; }
    public function setDescriptionAr(?string $descriptionAr): self { $this->descriptionAr = $descriptionAr; return $this; }
    public function isActif(): bool { return $this->actif; }
    public function setActif(bool $actif): self { $this->actif = $actif; return $this; }

    public function getReponses(): Collection { return $this->reponses; }
    public function addReponse(TestPsyReponse $reponse): self
    {
        if (!$this->reponses->contains($reponse)) {
            $this->reponses->add($reponse);
            $reponse->setScene($this);
        }
        return $this;
    }
    public function removeReponse(TestPsyReponse $reponse): self
    {
        if ($this->reponses->removeElement($reponse)) {
            if ($reponse->getScene() === $this) {
                $reponse->setScene(null);
            }
        }
        return $this;
    }
}
