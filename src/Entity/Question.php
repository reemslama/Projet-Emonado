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

    #[ORM\Column(nullable: true)]  // ✅ CHANGÉ ICI
    private ?int $ordre = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $typeQuestion = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $categorie = null;

    /**
     * @var Collection<int, Reponse>
     */
    #[ORM\OneToMany(mappedBy: 'question', targetEntity: Reponse::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $reponses;

    public function __construct()
    {
        $this->reponses = new ArrayCollection();
    }

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

    public function setOrdre(?int $ordre): self  // ✅ Ajoutez ? ici aussi
    {
        $this->ordre = $ordre;
        return $this;
    }

    public function getTypeQuestion(): ?string
    {
        return $this->typeQuestion;
    }

    public function setTypeQuestion(?string $typeQuestion): self
    {
        $this->typeQuestion = $typeQuestion;
        return $this;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(?string $categorie): self
    {
        $this->categorie = $categorie;
        return $this;
    }

    /**
     * @return Collection<int, Reponse>
     */
    public function getReponses(): Collection
    {
        return $this->reponses;
    }

    public function addReponse(Reponse $reponse): self
    {
        if (!$this->reponses->contains($reponse)) {
            $this->reponses->add($reponse);
            $reponse->setQuestion($this);
        }

        return $this;
    }

    public function removeReponse(Reponse $reponse): self
    {
        if ($this->reponses->removeElement($reponse)) {
            // set the owning side to null (unless already changed)
            if ($reponse->getQuestion() === $this) {
                $reponse->setQuestion(null);
            }
        }

        return $this;
    }
}