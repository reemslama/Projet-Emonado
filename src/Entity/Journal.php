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

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $contenu = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $humeur = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dateCreation = null;

    #[ORM\ManyToOne(inversedBy: 'journals')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\OneToOne(mappedBy: 'journal', cascade: ['persist', 'remove'])]
    private ?AnalyseEmotionnelle $analysisEmotionnelle = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $audioFileName = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $inputMode = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $transcriptionProvider = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $psychologueCaseDescription = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $psychologueReviewedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $patientAdviceSeenAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $musicPrescriptionJson = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $musicPrescriptionSource = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $musicPrescriptionObjective = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $musicPrescriptionGeneratedAt = null;

    public function __construct()
    {
        $this->dateCreation = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(?string $contenu): static
    {
        $this->contenu = $contenu;

        return $this;
    }

    public function getHumeur(): ?string
    {
        return $this->humeur;
    }

    public function setHumeur(?string $humeur): static
    {
        $this->humeur = $humeur;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function markCreatedAt(?\DateTimeImmutable $dateCreation = null): static
    {
        $this->dateCreation = $dateCreation ?? new \DateTimeImmutable();

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
        if ($analysisEmotionnelle->getJournal() !== $this) {
            $analysisEmotionnelle->setJournal($this);
        }

        $this->analysisEmotionnelle = $analysisEmotionnelle;

        return $this;
    }

    public function getAudioFileName(): ?string
    {
        return $this->audioFileName;
    }

    public function setAudioFileName(?string $audioFileName): static
    {
        $this->audioFileName = $audioFileName;

        return $this;
    }

    public function getInputMode(): ?string
    {
        return $this->inputMode;
    }

    public function setInputMode(?string $inputMode): static
    {
        $this->inputMode = $inputMode;

        return $this;
    }

    public function getTranscriptionProvider(): ?string
    {
        return $this->transcriptionProvider;
    }

    public function setTranscriptionProvider(?string $transcriptionProvider): static
    {
        $this->transcriptionProvider = $transcriptionProvider;

        return $this;
    }

    public function getPsychologueCaseDescription(): ?string
    {
        return $this->psychologueCaseDescription;
    }

    public function setPsychologueCaseDescription(?string $psychologueCaseDescription): static
    {
        $this->psychologueCaseDescription = $psychologueCaseDescription;

        return $this;
    }

    public function getPsychologueReviewedAt(): ?\DateTimeImmutable
    {
        return $this->psychologueReviewedAt;
    }

    public function markPsychologueReviewedAt(?\DateTimeImmutable $psychologueReviewedAt = null): static
    {
        $this->psychologueReviewedAt = $psychologueReviewedAt ?? new \DateTimeImmutable();

        return $this;
    }

    public function getPatientAdviceSeenAt(): ?\DateTimeImmutable
    {
        return $this->patientAdviceSeenAt;
    }

    public function markPatientAdviceSeenAt(?\DateTimeImmutable $patientAdviceSeenAt = null): static
    {
        $this->patientAdviceSeenAt = $patientAdviceSeenAt ?? new \DateTimeImmutable();

        return $this;
    }

    public function getMusicPrescriptionJson(): ?string
    {
        return $this->musicPrescriptionJson;
    }

    public function setMusicPrescriptionJson(?string $musicPrescriptionJson): static
    {
        $this->musicPrescriptionJson = $musicPrescriptionJson;

        return $this;
    }

    /**
     * @return array{tracks?:array<int, array{title:string,artist:string,url:string}>}|array{}
     */
    public function getMusicPrescriptionData(): array
    {
        if (!$this->musicPrescriptionJson) {
            return [];
        }

        try {
            $decoded = json_decode($this->musicPrescriptionJson, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : [];
        } catch (\JsonException) {
            return [];
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function setMusicPrescriptionData(array $data): static
    {
        $this->musicPrescriptionJson = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $this;
    }

    public function getMusicPrescriptionSource(): ?string
    {
        return $this->musicPrescriptionSource;
    }

    public function setMusicPrescriptionSource(?string $musicPrescriptionSource): static
    {
        $this->musicPrescriptionSource = $musicPrescriptionSource;

        return $this;
    }

    public function getMusicPrescriptionObjective(): ?string
    {
        return $this->musicPrescriptionObjective;
    }

    public function setMusicPrescriptionObjective(?string $musicPrescriptionObjective): static
    {
        $this->musicPrescriptionObjective = $musicPrescriptionObjective;

        return $this;
    }

    public function getMusicPrescriptionGeneratedAt(): ?\DateTimeImmutable
    {
        return $this->musicPrescriptionGeneratedAt;
    }

    public function markMusicPrescriptionGeneratedAt(?\DateTimeImmutable $musicPrescriptionGeneratedAt = null): static
    {
        $this->musicPrescriptionGeneratedAt = $musicPrescriptionGeneratedAt ?? new \DateTimeImmutable();

        return $this;
    }
}
