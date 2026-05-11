<?php

namespace App\Entity;

use App\Repository\RendezVousRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Table SQL `rendez_vous` (schéma prêt côté base).
 */
#[ORM\Entity(repositoryClass: RendezVousRepository::class)]
#[ORM\Table(name: 'rendez_vous')]
class RendezVous
{
    public const STATUT_EN_ATTENTE = 'en attente';

    public const STATUT_ACCEPTE = 'accepte';

    public const STATUT_REJETE = 'rejete';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?int $age = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $longitude = null;

    #[ORM\ManyToOne(inversedBy: 'rendezVouses')]
    #[ORM\JoinColumn(name: 'type_id', referencedColumnName: 'id', nullable: false)]
    #[Assert\NotBlank(message: 'Le type de rendez-vous est obligatoire')]
    private ?TypeRendezVous $type = null;

    #[ORM\ManyToOne(targetEntity: Disponibilite::class)]
    #[ORM\JoinColumn(name: 'dispo_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Disponibilite $disponibilite = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $patient = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $statut = null;

    #[ORM\Column(name: 'notes_patient', type: Types::TEXT, nullable: true)]
    private ?string $notesPatient = null;

    #[ORM\Column(name: 'notes_psychologue', type: Types::TEXT, nullable: true)]
    private ?string $notesPsychologue = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(?int $age): self
    {
        $this->age = $age;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): self
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * Compatibilité : nom affiché = patient lié (pas de colonne nom_patient en base).
     */
    public function getNomPatient(): ?string
    {
        if (!$this->patient instanceof User) {
            return null;
        }
        $nom = trim((string) $this->patient->getNom());
        $prenom = trim((string) $this->patient->getPrenom());
        $full = trim($nom . ' ' . $prenom);

        return $full !== '' ? $full : null;
    }

    /**
     * @deprecated Non persisté : le nom vient du compte patient
     */
    public function setNomPatient(?string $nomPatient): self
    {
        return $this;
    }

    /**
     * Compatibilité : pas de colonne CIN sur rendez_vous.
     */
    public function getCin(): ?string
    {
        return null;
    }

    public function setCin(?string $cin): self
    {
        return $this;
    }

    /**
     * Compatibilité : psychologue = celui du créneau disponibilite.
     */
    public function getNomPsychologue(): ?string
    {
        $psy = $this->disponibilite?->getPsychologue();
        if (!$psy instanceof User) {
            return null;
        }
        $nom = trim((string) $psy->getNom());
        $prenom = trim((string) $psy->getPrenom());
        $full = trim($nom . ' ' . $prenom);

        return $full !== '' ? $full : (string) $psy->getEmail();
    }

    /**
     * @deprecated Non persisté : défini par dispo_id
     */
    public function setNomPsychologue(?string $nomPsychologue): self
    {
        return $this;
    }

    /**
     * Date/heure du RDV = date du créneau + heure de début.
     */
    public function getDate(): ?\DateTimeInterface
    {
        $d = $this->disponibilite;
        if (!$d instanceof Disponibilite || !$d->getDate() || !$d->getHeureDebut()) {
            return null;
        }
        $dateStr = $d->getDate()->format('Y-m-d');
        $timeStr = $d->getHeureDebut()->format('H:i:s');
        $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $dateStr . ' ' . $timeStr);

        return $dt ?: null;
    }

    /**
     * @deprecated Préférer setDisponibilite() ; conservé pour l’API existante
     */
    public function scheduleAt(?\DateTimeInterface $date): self
    {
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

    public function getDisponibilite(): ?Disponibilite
    {
        return $this->disponibilite;
    }

    public function setDisponibilite(?Disponibilite $disponibilite): self
    {
        $this->disponibilite = $disponibilite;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): self
    {
        $this->statut = $statut;

        return $this;
    }

    public function getNotesPatient(): ?string
    {
        return $this->notesPatient;
    }

    public function setNotesPatient(?string $notesPatient): self
    {
        $this->notesPatient = $notesPatient;

        return $this;
    }

    public function getNotesPsychologue(): ?string
    {
        return $this->notesPsychologue;
    }

    public function setNotesPsychologue(?string $notesPsychologue): self
    {
        $this->notesPsychologue = $notesPsychologue;

        return $this;
    }

    /**
     * @return 'en_attente'|'accepte'|'rejete'
     */
    public static function normalizeStatut(?string $raw): string
    {
        $s = mb_strtolower(trim((string) $raw));
        // Gère 'accepte', 'acceptee', 'accepted', 'accepté', etc.
        if (str_contains($s, 'accept')) {
            return 'accepte';
        }
        // Gère 'rejete', 'rejetee', 'rejected', 'refuse', etc.
        if (str_contains($s, 'rejet') || str_contains($s, 'refus')) {
            return 'rejete';
        }
        // Gère 'en attente', 'pending', etc.
        if (str_contains($s, 'attent') || str_contains($s, 'pend')) {
            return 'en_attente';
        }

        return 'en_attente';
    }
}
