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

    #[ORM\Column(length: 255)]
    private ?string $emotionPrincipale = null;

    #[ORM\Column]
    private ?int $niveauStress = null;

    #[ORM\Column]
    private ?int $scoreBienEtre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $resumeIA = null;

    #[ORM\Column]
    private ?\DateTime $dateAnalyse = null;

    #[ORM\OneToOne(inversedBy: 'analysisEmotionnelle', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Journal $journal = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmotionPrincipale(): ?string
    {
        return $this->emotionPrincipale;
    }

    public function setEmotionPrincipale(string $emotionPrincipale): static
    {
        $this->emotionPrincipale = $emotionPrincipale;

        return $this;
    }

    public function getNiveauStress(): ?int
    {
        return $this->niveauStress;
    }

    public function setNiveauStress(int $niveauStress): static
    {
        $this->niveauStress = $niveauStress;

        return $this;
    }

    public function getScoreBienEtre(): ?int
    {
        return $this->scoreBienEtre;
    }

    public function setScoreBienEtre(int $scoreBienEtre): static
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

    public function getDateAnalyse(): ?\DateTime
    {
        return $this->dateAnalyse;
    }

    public function setDateAnalyse(\DateTime $dateAnalyse): static
    {
        $this->dateAnalyse = $dateAnalyse;

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
