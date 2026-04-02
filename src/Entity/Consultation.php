<?php

namespace App\Entity;

use App\Repository\ConsultationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Traits\TimestampableTrait;
use App\Entity\Traits\BlameableTrait;

#[ORM\Entity(repositoryClass: ConsultationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Consultation
{
    use TimestampableTrait;
    use BlameableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $compteRendu = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $humeurPatient = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $sujetAborde = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $observations = null;

    #[ORM\ManyToOne(targetEntity: DossierMedical::class, inversedBy: 'consultations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?DossierMedical $dossier = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $psychologue = null;

    /** @var Collection<int, ConsultationDocument> */
    #[ORM\OneToMany(mappedBy: 'consultation', targetEntity: ConsultationDocument::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $documents;

    /** @var Collection<int, Prescription> */
    #[ORM\OneToMany(mappedBy: 'consultation', targetEntity: Prescription::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $prescriptions;

    public function __construct()
    {
        $this->documents = new ArrayCollection();
        $this->prescriptions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCompteRendu(): ?string
    {
        return $this->compteRendu;
    }

    public function setCompteRendu(?string $compteRendu): self
    {
        $this->compteRendu = $compteRendu;

        return $this;
    }

    public function getHumeurPatient(): ?string
    {
        return $this->humeurPatient;
    }

    public function setHumeurPatient(?string $humeurPatient): self
    {
        $this->humeurPatient = $humeurPatient;
        return $this;
    }

    public function getSujetAborde(): ?string
    {
        return $this->sujetAborde;
    }

    public function setSujetAborde(?string $sujetAborde): self
    {
        $this->sujetAborde = $sujetAborde;
        return $this;
    }

    public function getObservations(): ?string
    {
        return $this->observations;
    }

    public function setObservations(?string $observations): self
    {
        $this->observations = $observations;
        return $this;
    }

    public function getDossier(): ?DossierMedical
    {
        return $this->dossier;
    }

    public function setDossier(?DossierMedical $dossier): self
    {
        $this->dossier = $dossier;

        return $this;
    }

    public function getPsychologue(): ?User
    {
        return $this->psychologue;
    }

    public function setPsychologue(?User $psychologue): self
    {
        $this->psychologue = $psychologue;

        return $this;
    }

    /** @return Collection<int, ConsultationDocument> */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(ConsultationDocument $doc): self
    {
        if (!$this->documents->contains($doc)) {
            $this->documents->add($doc);
            $doc->setConsultation($this);
        }
        return $this;
    }

    public function removeDocument(ConsultationDocument $doc): self
    {
        if ($this->documents->removeElement($doc) && $doc->getConsultation() === $this) {
            $doc->setConsultation(null);
        }
        return $this;
    }

    /** @return Collection<int, Prescription> */
    public function getPrescriptions(): Collection
    {
        return $this->prescriptions;
    }

    public function addPrescription(Prescription $p): self
    {
        if (!$this->prescriptions->contains($p)) {
            $this->prescriptions->add($p);
            $p->setConsultation($this);
        }
        return $this;
    }

    public function removePrescription(Prescription $p): self
    {
        if ($this->prescriptions->removeElement($p) && $p->getConsultation() === $this) {
            $p->setConsultation(null);
        }
        return $this;
    }
}
