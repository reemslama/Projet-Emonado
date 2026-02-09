<?php

namespace App\Entity;

use App\Repository\QuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuestionRepository::class)]
class Question
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $texte = null;

    #[ORM\Column]
    private ?int $ordre = null;

    #[ORM\Column(length: 50)]
    private ?string $typeQuestion = null;

    #[ORM\Column(length: 50)]
    private ?string $categorie = null;

    #[ORM\OneToMany(mappedBy: 'question', targetEntity: Reponse::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $reponses;

    public function __construct()
    {
        $this->reponses = new ArrayCollection();
    }

    // GETTERS / SETTERS

    public function getId(): ?int 
    { 
        return $this->id; 
    }

    public function getTexte(): ?string 
    { 
        return $this->texte; 
    }

    public function setTexte(string $texte): self 
    { 
        $this->texte = $texte; 
        return $this; 
    }

    public function getOrdre(): ?int 
    { 
        return $this->ordre; 
    }

    public function setOrdre(int $ordre): self 
    { 
        $this->ordre = $ordre; 
        return $this; 
    }

    public function getTypeQuestion(): ?string 
    { 
        return $this->typeQuestion; 
    }

    public function setTypeQuestion(string $type): self 
    { 
        $this->typeQuestion = $type; 
        return $this; 
    }

    public function getCategorie(): ?string 
    { 
        return $this->categorie; 
    }

    public function setCategorie(string $cat): self 
    { 
        $this->categorie = $cat; 
        return $this; 
    }

    public function getReponses(): Collection 
    { 
        return $this->reponses; 
    }

    public function addReponse(Reponse $reponse): self
    {
        if (!$this->reponses->contains($reponse)) {
            $this->reponses[] = $reponse;
            $reponse->setQuestion($this);
        }
        return $this;
    }

    // ✅ MÉTHODE MANQUANTE AJOUTÉE
    public function removeReponse(Reponse $reponse): self
    {
        if ($this->reponses->removeElement($reponse)) {
            // Défaire la relation bidirectionnelle
            if ($reponse->getQuestion() === $this) {
                $reponse->setQuestion(null);
            }
        }
        return $this;
    }
}