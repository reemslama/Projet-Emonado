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
    #[Assert\NotBlank(message: "Le nom du patient est obligatoire")]
    private ?string $nom_patient = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: "Le CIN est obligatoire")]
    private ?string $cin = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom du psychologue est obligatoire")]
    private ?string $nom_psychologue = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull(message: "La date est obligatoire.")]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(length: 100)]
    private ?string $status = "en attente";

    #[ORM\ManyToOne(targetEntity: TypeRendezVous::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?TypeRendezVous $type = null;

    // --- GETTERS (Crucial pour Twig) ---
    public function getId(): ?int { return $this->id; }

    public function getNomPatient(): ?string { return $this->nom_patient; }
    public function setNomPatient(?string $nom_patient): self { $this->nom_patient = $nom_patient; return $this; }

    public function getCin(): ?string { return $this->cin; }
    public function setCin(?string $cin): self { $this->cin = $cin; return $this; }

    public function getNomPsychologue(): ?string { return $this->nom_psychologue; }
    public function setNomPsychologue(?string $nom_psychologue): self { $this->nom_psychologue = $nom_psychologue; return $this; }

    public function getDate(): ?\DateTimeInterface { return $this->date; }
    public function setDate(?\DateTimeInterface $date): self { $this->date = $date; return $this; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(?string $status): self { $this->status = $status; return $this; }

    public function getType(): ?TypeRendezVous { return $this->type; }
    public function setType(?TypeRendezVous $type): self { $this->type = $type; return $this; }
}