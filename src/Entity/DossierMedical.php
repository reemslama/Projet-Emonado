<?php

namespace App\Entity;

use App\Repository\DossierMedicalRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DossierMedicalRepository::class)]
class DossierMedical
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank(message: "L'historique médical est obligatoire")]
    #[Assert\Length(min: 10, minMessage: "L'historique médical doit contenir au moins {{ limit }} caractères")]
    private ?string $historiqueMedical = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank(message: "Les notes psychologiques sont obligatoires")]
    #[Assert\Length(min: 10, minMessage: "Les notes psychologiques doivent contenir au moins {{ limit }} caractères")]
    private ?string $notesPsychologiques = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?User $patient = null;

    #[ORM\OneToMany(mappedBy: 'dossier', targetEntity: Consultation::class, cascade: ['persist', 'remove'])]
    private Collection $consultations;

    public function __construct()
    {
        $this->consultations = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHistoriqueMedical(): ?string
    {
        return $this->historiqueMedical;
    }

    public function setHistoriqueMedical(?string $historiqueMedical): self
    {
        $this->historiqueMedical = $historiqueMedical;
        return $this;
    }

    public function getNotesPsychologiques(): ?string
    {
        return $this->notesPsychologiques;
    }

    public function setNotesPsychologiques(?string $notesPsychologiques): self
    {
        $this->notesPsychologiques = $notesPsychologiques;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getPatient(): ?User
    {
        return $this->patient;
    }

    public function setPatient(?User $patient): self
    {
        $this->patient = $patient;
        return $this;
    }

    /**
     * @return Collection<int, Consultation>
     */
    public function getConsultations(): Collection
    {
        return $this->consultations;
    }

    public function addConsultation(Consultation $consultation): self
    {
        if (!$this->consultations->contains($consultation)) {
            $this->consultations->add($consultation);
            $consultation->setDossier($this);
        }

        return $this;
    }

    public function removeConsultation(Consultation $consultation): self
    {
        if ($this->consultations->removeElement($consultation)) {
            // set the owning side to null (unless already changed)
            if ($consultation->getDossier() === $this) {
                $consultation->setDossier(null);
            }
        }

        return $this;
    }
}