<?php

namespace App\Entity;

use App\Repository\PrescriptionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\TimestampableTrait;
use App\Entity\Traits\BlameableTrait;

#[ORM\Entity(repositoryClass: PrescriptionRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Prescription
{
    use TimestampableTrait;
    use BlameableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $contenu = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $datePrescription;

    #[ORM\ManyToOne(targetEntity: Consultation::class, inversedBy: 'prescriptions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Consultation $consultation = null;

    public function __construct()
    {
        $this->datePrescription = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(?string $contenu): self
    {
        $this->contenu = $contenu;
        return $this;
    }

    public function getDatePrescription(): \DateTimeImmutable
    {
        return $this->datePrescription;
    }
    public function refreshDatePrescription(): void
    {
        $this->datePrescription = new \DateTimeImmutable();
    }

    public function getConsultation(): ?Consultation
    {
        return $this->consultation;
    }

    public function setConsultation(?Consultation $consultation): self
    {
        $this->consultation = $consultation;
        return $this;
    }
}
