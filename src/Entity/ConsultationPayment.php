<?php

namespace App\Entity;

use App\Repository\ConsultationPaymentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Table SQL `consultation_payment` (montants en centimes / sous-unité à 2 décimales : 50,00 → 5000).
 */
#[ORM\Entity(repositoryClass: ConsultationPaymentRepository::class)]
#[ORM\Table(name: 'consultation_payment')]
#[ORM\HasLifecycleCallbacks]
class ConsultationPayment
{
    public const STATUT_PENDING = 'pending';

    public const STATUT_PAID = 'paid';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: RendezVous::class)]
    #[ORM\JoinColumn(name: 'rendez_vous_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?RendezVous $rendezVous = null;

    #[ORM\ManyToOne(targetEntity: Consultation::class)]
    #[ORM\JoinColumn(name: 'consultation_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Consultation $consultation = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'patient_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $patient = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'psychologue_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $psychologue = null;

    #[ORM\Column(name: 'stripe_session_id', length: 255, nullable: true)]
    private ?string $stripeSessionId = null;

    #[ORM\Column(name: 'stripe_payment_intent_id', length: 255, nullable: true)]
    private ?string $stripePaymentIntentId = null;

    #[ORM\Column(name: 'checkout_url', type: Types::TEXT, nullable: true)]
    private ?string $checkoutUrl = null;

    #[ORM\Column(name: 'amount_cents')]
    private int $amountCents = 0;

    #[ORM\Column(name: 'currency', length: 10)]
    private string $currency = 'TND';

    #[ORM\Column(name: 'status', length: 30)]
    private string $status = self::STATUT_PENDING;

    #[ORM\Column(name: 'paid_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();
        if ($this->createdAt === null) {
            $this->createdAt = $now;
        }
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getConsultation(): ?Consultation
    {
        return $this->consultation;
    }

    public function setConsultation(?Consultation $consultation): self
    {
        $this->consultation = $consultation;

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

    public function getStripeSessionId(): ?string
    {
        return $this->stripeSessionId;
    }

    public function setStripeSessionId(?string $stripeSessionId): self
    {
        $this->stripeSessionId = $stripeSessionId;

        return $this;
    }

    public function getStripePaymentIntentId(): ?string
    {
        return $this->stripePaymentIntentId;
    }

    public function setStripePaymentIntentId(?string $stripePaymentIntentId): self
    {
        $this->stripePaymentIntentId = $stripePaymentIntentId;

        return $this;
    }

    public function getCheckoutUrl(): ?string
    {
        return $this->checkoutUrl;
    }

    public function setCheckoutUrl(?string $checkoutUrl): self
    {
        $this->checkoutUrl = $checkoutUrl;

        return $this;
    }

    public function getAmountCents(): int
    {
        return $this->amountCents;
    }

    public function setAmountCents(int $amountCents): self
    {
        $this->amountCents = $amountCents;

        return $this;
    }

    /** Montant affichable (major unit), ex. 50.00 pour 5000 centimes. */
    public function getMontant(): string
    {
        return number_format($this->amountCents / 100, 2, '.', '');
    }

    public function setMontant(string|float $montant): self
    {
        $raw = is_string($montant) ? str_replace([' ', ','], ['', '.'], $montant) : (string) $montant;
        $f = (float) $raw;
        $this->amountCents = (int) round($f * 100);

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getDevise(): string
    {
        return $this->currency;
    }

    public function setDevise(string $devise): self
    {
        $this->currency = $devise;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatut(): string
    {
        return $this->status;
    }

    public function setStatut(string $statut): self
    {
        $this->status = $statut;

        return $this;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUT_PAID;
    }

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function setPaidAt(?\DateTimeImmutable $paidAt): self
    {
        $this->paidAt = $paidAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /** Référence locale / intent : mappée sur `stripe_payment_intent_id`. */
    public function getReferenceLocale(): ?string
    {
        return $this->stripePaymentIntentId;
    }

    public function setReferenceLocale(?string $referenceLocale): self
    {
        $this->stripePaymentIntentId = $referenceLocale;

        return $this;
    }
}
