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

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Le contenu du journal est obligatoire.')]
    #[Assert\Length(
        min: 10,
        minMessage: 'Le contenu doit contenir au moins {{ limit }} caracteres.',
        max: 1000,
        maxMessage: 'Le contenu ne peut pas depasser {{ limit }} caracteres.'
    )]
    private ?string $contenu = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Veuillez choisir votre humeur.')]
    #[Assert\Choice(
        choices: ['heureux', 'SOS', 'en colere', 'calme'],
        message: "Veuillez choisir une humeur valide: 'heureux', 'SOS', 'en colere', 'calme'."
    )]
    private ?string $humeur = null;

    #[ORM\Column]
    private ?\DateTime $dateCreation = null;

    #[ORM\ManyToOne(inversedBy: 'journals')]
    #[ORM\JoinColumn(nullable: true)]
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
    private ?\DateTime $psychologueReviewedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $patientAdviceSeenAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $musicPrescriptionJson = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $musicPrescriptionSource = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $musicPrescriptionObjective = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $musicPrescriptionGeneratedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;

        return $this;
    }

    public function getHumeur(): ?string
    {
        return $this->humeur;
    }

    public function setHumeur(string $humeur): static
    {
        $this->humeur = $humeur;

        return $this;
    }

    public function getDateCreation(): ?\DateTime
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTime $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

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

    public function getPsychologueReviewedAt(): ?\DateTime
    {
        return $this->psychologueReviewedAt;
    }

    public function setPsychologueReviewedAt(?\DateTime $psychologueReviewedAt): static
    {
        $this->psychologueReviewedAt = $psychologueReviewedAt;

        return $this;
    }

    public function getPatientAdviceSeenAt(): ?\DateTime
    {
        return $this->patientAdviceSeenAt;
    }

    public function setPatientAdviceSeenAt(?\DateTime $patientAdviceSeenAt): static
    {
        $this->patientAdviceSeenAt = $patientAdviceSeenAt;

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

    public function getMusicPrescriptionGeneratedAt(): ?\DateTime
    {
        return $this->musicPrescriptionGeneratedAt;
    }

    public function setMusicPrescriptionGeneratedAt(?\DateTime $musicPrescriptionGeneratedAt): static
    {
        $this->musicPrescriptionGeneratedAt = $musicPrescriptionGeneratedAt;

        return $this;
    }
}
