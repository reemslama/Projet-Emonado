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
    private ?string $nomPatient = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: "Le CIN est obligatoire")]
    private ?string $cin = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom du psychologue est obligatoire")]
    private ?string $nomPsychologue = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date = null;

    #[ORM\ManyToOne(inversedBy: 'rendezVouses')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(message: "Le type de rendez-vous est obligatoire")]
    private ?TypeRendezVous $type = null;

    #[ORM\ManyToOne(inversedBy: 'rendezVouses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $patient = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomPatient(): ?string
    {
        return $this->nomPatient;
    }

    public function setNomPatient(string $nomPatient): self
    {
        $this->nomPatient = $nomPatient;
        return $this;
    }

    public function getCin(): ?string
    {
        return $this->cin;
    }

    public function setCin(string $cin): self
    {
        $this->cin = $cin;
        return $this;
    }

    public function getNomPsychologue(): ?string
    {
        return $this->nomPsychologue;
    }

    public function setNomPsychologue(string $nomPsychologue): self
    {
        $this->nomPsychologue = $nomPsychologue;
        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getType(): ?TypeRendezVous
    {
        return $this->type;
    }

    public function setType(?TypeRendezVous $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getPatient(): ?User
    {
        return $this->patient;
    }

    public function setPatient(?User $patient): static
    {
        $this->patient = $patient;

        return $this;
    }
}