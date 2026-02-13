<?php

namespace App\Entity;

use App\Repository\RendezVousRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RendezVousRepository::class)]
class RendezVous
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom du patient est obligatoire')]
    #[Assert\Length(min: 2, max: 255)]
    #[Assert\Regex(
        pattern: '/^[a-zA-ZÀ-ÿ\s\-]+$/',
        message: 'Le nom ne peut contenir que des lettres, espaces et tirets'
    )]
    private ?string $nomPatient = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Le CIN est obligatoire')]
    #[Assert\Regex(
        pattern: '/^[0-9]{8,12}$/',
        message: 'Le CIN doit contenir entre 8 et 12 chiffres'
    )]
    private ?string $cin = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom du psychologue est obligatoire')]
    #[Assert\Length(min: 2, max: 255)]
    private ?string $nomPsychologue = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: 'La date est obligatoire')]
    #[Assert\Type(\DateTimeInterface::class)]
    private ?\DateTimeInterface $date = null;

    #[ORM\ManyToOne(inversedBy: 'rendezVouses')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(message: 'Le type de rendez-vous est obligatoire')]
    private ?TypeRendezVous $type = null;

    // ============================================
    // ✅ GETTERS
    // ============================================
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomPatient(): ?string  // ✅ MANQUANT - À AJOUTER
    {
        return $this->nomPatient;
    }

    public function getCin(): ?string  // ✅ MANQUANT - À AJOUTER
    {
        return $this->cin;
    }

    public function getNomPsychologue(): ?string  // ✅ MANQUANT - À AJOUTER
    {
        return $this->nomPsychologue;
    }

    public function getDate(): ?\DateTimeInterface  // ✅ MANQUANT - À AJOUTER
    {
        return $this->date;
    }

    public function getType(): ?TypeRendezVous  // ✅ MANQUANT - À AJOUTER
    {
        return $this->type;
    }

    // ============================================
    // ✅ SETTERS
    // ============================================
    
    public function setNomPatient(string $nomPatient): self  // ✅ MANQUANT - À AJOUTER
    {
        $this->nomPatient = $nomPatient;
        return $this;
    }

    public function setCin(string $cin): self  // ✅ MANQUANT - À AJOUTER
    {
        $this->cin = $cin;
        return $this;
    }

    public function setNomPsychologue(string $nomPsychologue): self  // ✅ MANQUANT - À AJOUTER
    {
        $this->nomPsychologue = $nomPsychologue;
        return $this;
    }

    public function setDate(\DateTimeInterface $date): self  // ✅ MANQUANT - À AJOUTER
    {
        $this->date = $date;
        return $this;
    }

    public function setType(?TypeRendezVous $type): self  // ✅ MANQUANT - À AJOUTER
    {
        $this->type = $type;
        return $this;
    }
}