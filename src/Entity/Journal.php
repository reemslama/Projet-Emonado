<?php

namespace App\Entity;

use App\Repository\JournalRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: JournalRepository::class)]
class Journal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "Le contenu du journal est obligatoire.")]
    #[Assert\Length(
        min: 10,
        minMessage: "Le contenu doit contenir au moins {{ limit }} caractères.",
        max: 1000,
        maxMessage: "Le contenu ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $contenu = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Veuillez choisir votre humeur.")]
    #[Assert\Choice(
        choices: ['heureux', 'SOS', 'en colere', 'calme'],
        message: "Veuillez choisir une humeur valide: 'heureux', 'SOS', 'en colere', 'calme'."
    )]
    private ?string $humeur = null;

    #[ORM\Column]
    private ?\DateTime $dateCreation = null;

    #[ORM\ManyToOne(inversedBy: 'journals')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\OneToOne(mappedBy: 'journal', cascade: ['persist', 'remove'])]
    private ?AnalyseEmotionnelle $analysisEmotionnelle = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;

        return $this;
    }

    public function getHumeur(): ?string
    {
        return $this->humeur;
    }

    public function setHumeur(string $humeur): static
    {
        $this->humeur = $humeur;

        return $this;
    }

    public function getDateCreation(): ?\DateTime
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTime $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getAnalysisEmotionnelle(): ?AnalyseEmotionnelle
    {
        return $this->analysisEmotionnelle;
    }

    public function setAnalysisEmotionnelle(AnalyseEmotionnelle $analysisEmotionnelle): static
    {
        // set the owning side of the relation if necessary
        if ($analysisEmotionnelle->getJournal() !== $this) {
            $analysisEmotionnelle->setJournal($this);
        }

        $this->analysisEmotionnelle = $analysisEmotionnelle;

        return $this;
    }
}
