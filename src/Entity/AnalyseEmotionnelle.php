<?php

namespace App\Entity;

use App\Repository\AnalyseEmotionnelleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AnalyseEmotionnelleRepository::class)]
class AnalyseEmotionnelle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'emotion_principale', length: 255, nullable: true)]
    private ?string $etatEmotionnel = null;

    #[ORM\Column(name: 'niveau_stress', type: Types::STRING, length: 255, nullable: true)]
    private ?string $niveau = null;

    #[ORM\Column(name: 'score_bien_etre', nullable: true)]
    private ?int $scoreBienEtre = null;

    #[ORM\Column(name: 'resume_ia', type: Types::TEXT, nullable: true)]
    private ?string $resumeIA = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateAnalyse = null;

    #[ORM\OneToOne(inversedBy: 'analysisEmotionnelle', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Journal $journal = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEtatEmotionnel(): ?string
    {
        return $this->etatEmotionnel;
    }

    public function setEtatEmotionnel(?string $etatEmotionnel): static
    {
        $this->etatEmotionnel = $etatEmotionnel;

        return $this;
    }

    public function getNiveau(): ?string
    {
        return $this->niveau;
    }

    public function setNiveau(?string $niveau): static
    {
        $this->niveau = $niveau;

        return $this;
    }

    public function getScoreBienEtre(): ?int
    {
        return $this->scoreBienEtre;
    }

    public function setScoreBienEtre(?int $scoreBienEtre): static
    {
        $this->scoreBienEtre = $scoreBienEtre;

        return $this;
    }

    public function getResumeIA(): ?string
    {
        return $this->resumeIA;
    }

    public function setResumeIA(?string $resumeIA): static
    {
        $this->resumeIA = $resumeIA;

        return $this;
    }

    public function getDeclencheur(): ?string
    {
        if ($this->resumeIA === null) {
            return null;
        }
        $parts = explode("\n---\n", $this->resumeIA, 2);
        return $parts[0] ?? null;
    }

    public function setDeclencheur(?string $declencheur): static
    {
        $conseil = $this->getConseil();
        $this->resumeIA = $declencheur . "\n---\n" . $conseil;
        return $this;
    }

    public function getConseil(): ?string
    {
        if ($this->resumeIA === null) {
            return null;
        }
        $parts = explode("\n---\n", $this->resumeIA, 2);
        return $parts[1] ?? null;
    }

    public function setConseil(?string $conseil): static
    {
        $declencheur = $this->getDeclencheur();
        $this->resumeIA = $declencheur . "\n---\n" . $conseil;
        return $this;
    }

    public function getDateAnalyse(): ?\DateTimeImmutable
    {
        return $this->dateAnalyse;
    }

    public function markAnalyzedAt(?\DateTimeImmutable $dateAnalyse = null): static
    {
        $this->dateAnalyse = $dateAnalyse ?? new \DateTimeImmutable();

        return $this;
    }

    public function getJournal(): ?Journal
    {
        return $this->journal;
    }

    public function setJournal(Journal $journal): static
    {
        $this->journal = $journal;

        return $this;
    }
}
