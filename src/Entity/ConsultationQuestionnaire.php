<?php

namespace App\Entity;

use App\Repository\ConsultationQuestionnaireRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Table SQL `consultation_questionnaire` : clé métier = payment_id (pas consultation_id).
 */
#[ORM\Entity(repositoryClass: ConsultationQuestionnaireRepository::class)]
#[ORM\Table(name: 'consultation_questionnaire')]
#[ORM\HasLifecycleCallbacks]
class ConsultationQuestionnaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ConsultationPayment::class)]
    #[ORM\JoinColumn(name: 'payment_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?ConsultationPayment $payment = null;

    #[ORM\ManyToOne(targetEntity: RendezVous::class)]
    #[ORM\JoinColumn(name: 'rendez_vous_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?RendezVous $rendezVous = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'patient_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $patient = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'psychologue_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $psychologue = null;

    #[ORM\Column(name: 'chief_complaint', type: Types::TEXT)]
    private string $motifPrincipal = '';

    #[ORM\Column(name: 'symptom_summary', type: Types::TEXT)]
    private string $symptomesObserves = '';

    #[ORM\Column(name: 'voice_transcript', type: Types::TEXT, nullable: true)]
    private ?string $noteVocaleTranscrite = null;

    #[ORM\Column(name: 'stress_level')]
    private int $stress = 0;

    #[ORM\Column(name: 'anxiety_level')]
    private int $anxiete = 0;

    #[ORM\Column(name: 'mood_level')]
    private int $humeur = 0;

    #[ORM\Column(name: 'sleep_quality')]
    private int $sommeil = 0;

    #[ORM\Column(name: 'energy_level')]
    private int $energie = 0;

    #[ORM\Column(name: 'support_level')]
    private int $soutien = 0;

    #[ORM\Column(name: 'urgency_level')]
    private int $urgenceRessentie = 0;

    #[ORM\Column(name: 'self_harm_risk', length: 30)]
    private string $risqueAutoAgression = 'none';

    #[ORM\Column(name: 'additional_context', type: Types::TEXT, nullable: true)]
    private ?string $contexteComplementaire = null;

    #[ORM\Column(name: 'risk_score')]
    private int $riskScore = 0;

    #[ORM\Column(name: 'predicted_state', length: 255)]
    private string $predictedState = '';

    #[ORM\Column(name: 'submitted_at', type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $submittedAt = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();
        $this->submittedAt = $now;
        if ($this->createdAt === null) {
            $this->createdAt = $now;
        }
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $now = new \DateTimeImmutable();
        $this->updatedAt = $now;
        $this->submittedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPayment(): ?ConsultationPayment
    {
        return $this->payment;
    }

    public function setPayment(?ConsultationPayment $payment): self
    {
        $this->payment = $payment;

        return $this;
    }

    /** Consultation technique pré-consultation liée au paiement (si renseignée). */
    public function getConsultation(): ?Consultation
    {
        return $this->payment?->getConsultation();
    }

    public function getRendezVous(): ?RendezVous
    {
        return $this->rendezVous;
    }

    public function setRendezVous(?RendezVous $rendezVous): self
    {
        $this->rendezVous = $rendezVous;

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

    public function getPsychologue(): ?User
    {
        return $this->psychologue;
    }

    public function setPsychologue(?User $psychologue): self
    {
        $this->psychologue = $psychologue;

        return $this;
    }

    public function getMotifPrincipal(): string
    {
        return $this->motifPrincipal;
    }

    public function setMotifPrincipal(?string $motifPrincipal): self
    {
        $this->motifPrincipal = $motifPrincipal ?? '';

        return $this;
    }

    public function getSymptomesObserves(): string
    {
        return $this->symptomesObserves;
    }

    public function setSymptomesObserves(?string $symptomesObserves): self
    {
        $this->symptomesObserves = $symptomesObserves ?? '';

        return $this;
    }

    public function getNoteVocaleTranscrite(): ?string
    {
        return $this->noteVocaleTranscrite;
    }

    public function setNoteVocaleTranscrite(?string $noteVocaleTranscrite): self
    {
        $this->noteVocaleTranscrite = $noteVocaleTranscrite;

        return $this;
    }

    public function getStress(): int
    {
        return $this->stress;
    }

    public function setStress(int $stress): self
    {
        $this->stress = max(0, min(10, $stress));

        return $this;
    }

    public function getAnxiete(): int
    {
        return $this->anxiete;
    }

    public function setAnxiete(int $anxiete): self
    {
        $this->anxiete = max(0, min(10, $anxiete));

        return $this;
    }

    public function getHumeur(): int
    {
        return $this->humeur;
    }

    public function setHumeur(int $humeur): self
    {
        $this->humeur = max(0, min(10, $humeur));

        return $this;
    }

    public function getSommeil(): int
    {
        return $this->sommeil;
    }

    public function setSommeil(int $sommeil): self
    {
        $this->sommeil = max(0, min(10, $sommeil));

        return $this;
    }

    public function getEnergie(): int
    {
        return $this->energie;
    }

    public function setEnergie(int $energie): self
    {
        $this->energie = max(0, min(10, $energie));

        return $this;
    }

    public function getSoutien(): int
    {
        return $this->soutien;
    }

    public function setSoutien(int $soutien): self
    {
        $this->soutien = max(0, min(10, $soutien));

        return $this;
    }

    public function getUrgenceRessentie(): int
    {
        return $this->urgenceRessentie;
    }

    public function setUrgenceRessentie(int $urgenceRessentie): self
    {
        $this->urgenceRessentie = max(0, min(10, $urgenceRessentie));

        return $this;
    }

    public function getRisqueAutoAgression(): string
    {
        return $this->risqueAutoAgression;
    }

    public function setRisqueAutoAgression(?string $risqueAutoAgression): self
    {
        $this->risqueAutoAgression = $risqueAutoAgression ?? 'none';

        return $this;
    }

    public function getContexteComplementaire(): ?string
    {
        return $this->contexteComplementaire;
    }

    public function setContexteComplementaire(?string $contexteComplementaire): self
    {
        $this->contexteComplementaire = $contexteComplementaire;

        return $this;
    }

    public function getRiskScore(): int
    {
        return $this->riskScore;
    }

    public function setRiskScore(int $riskScore): self
    {
        $this->riskScore = $riskScore;

        return $this;
    }

    public function getPredictedState(): string
    {
        return $this->predictedState;
    }

    public function setPredictedState(?string $predictedState): self
    {
        $this->predictedState = $predictedState ?? '';

        return $this;
    }

    public function getSubmittedAt(): ?\DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
