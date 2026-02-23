<?php

namespace App\Entity;

use App\Repository\TestAdaptatifRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TestAdaptatifRepository::class)]
class TestAdaptatif
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $patient = null;

    #[ORM\Column(length: 50)]
    private ?string $categorie = null;

    #[ORM\Column(type: Types::JSON)]
    private array $questionsReponses = [];

    #[ORM\Column]
    private ?int $scoreActuel = 0;

    #[ORM\Column]
    private ?int $nombreQuestions = 0;

    #[ORM\Column]
    private ?bool $termine = false;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateDebut = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dateFin = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $analyse = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $profilPatient = null;

    public function __construct()
    {
        $this->dateDebut = new \DateTimeImmutable();
        $this->questionsReponses = [];
        $this->scoreActuel = 0;
        $this->nombreQuestions = 0;
        $this->termine = false;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(string $categorie): self
    {
        $this->categorie = $categorie;
        return $this;
    }

    public function getQuestionsReponses(): array
    {
        return $this->questionsReponses;
    }

    public function setQuestionsReponses(array $questionsReponses): self
    {
        $this->questionsReponses = $questionsReponses;
        return $this;
    }

    public function ajouterQuestionReponse(string $question, string $reponse, int $valeur): self
    {
        $this->questionsReponses[] = [
            'question' => $question,
            'reponse' => $reponse,
            'valeur' => $valeur,
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s')
        ];
        $this->scoreActuel += $valeur;
        $this->nombreQuestions++;
        return $this;
    }

    public function getScoreActuel(): ?int
    {
        return $this->scoreActuel;
    }

    public function setScoreActuel(int $scoreActuel): self
    {
        $this->scoreActuel = $scoreActuel;
        return $this;
    }

    public function getNombreQuestions(): ?int
    {
        return $this->nombreQuestions;
    }

    public function setNombreQuestions(int $nombreQuestions): self
    {
        $this->nombreQuestions = $nombreQuestions;
        return $this;
    }

    public function isTermine(): ?bool
    {
        return $this->termine;
    }

    public function setTermine(bool $termine): self
    {
        $this->termine = $termine;
        if ($termine && !$this->dateFin) {
            $this->dateFin = new \DateTimeImmutable();
        }
        return $this;
    }

    public function getDateDebut(): ?\DateTimeImmutable
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeImmutable $dateDebut): self
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeImmutable
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeImmutable $dateFin): self
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getAnalyse(): ?string
    {
        return $this->analyse;
    }

    public function setAnalyse(?string $analyse): self
    {
        $this->analyse = $analyse;
        return $this;
    }

    public function getProfilPatient(): ?array
    {
        return $this->profilPatient;
    }

    public function setProfilPatient(?array $profilPatient): self
    {
        $this->profilPatient = $profilPatient;
        return $this;
    }
}
