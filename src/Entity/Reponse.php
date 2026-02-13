<?php

namespace App\Entity;

use App\Repository\ReponseRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReponseRepository::class)]
class Reponse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $texte = null;

    #[ORM\Column]
    private ?int $valeur = null;

    #[ORM\Column]
    private ?int $ordre = null;

    #[ORM\ManyToOne(inversedBy: 'reponses')]
    private ?Question $question = null;

    // GETTERS / SETTERS

    public function getId(): ?int { return $this->id; }

    public function getTexte(): ?string { return $this->texte; }
    public function setTexte(string $texte): self { $this->texte = $texte; return $this; }

    public function getValeur(): ?int { return $this->valeur; }
    public function setValeur(int $valeur): self { $this->valeur = $valeur; return $this; }

    public function getOrdre(): ?int { return $this->ordre; }
    public function setOrdre(int $ordre): self { $this->ordre = $ordre; return $this; }

    public function getQuestion(): ?Question { return $this->question; }
    public function setQuestion(?Question $q): self { $this->question = $q; return $this; }
}
