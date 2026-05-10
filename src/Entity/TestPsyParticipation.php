<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: 'test_psy_participation')]
class TestPsyParticipation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $dateTest = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $rapportIa = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $scoreAnxiete = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $scoreTristesse = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $scoreColere = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $scoreJoie = 0;



    public function __construct()
    {
        $this->dateTest = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }

    public function getDateTest(): ?\DateTimeInterface { return $this->dateTest; }
    public function setDateTest(\DateTimeInterface $dateTest): self { $this->dateTest = $dateTest; return $this; }

    public function getRapportIa(): ?string { return $this->rapportIa; }
    public function setRapportIa(?string $rapportIa): self { $this->rapportIa = $rapportIa; return $this; }

    public function getScoreAnxiete(): int { return $this->scoreAnxiete; }
    public function setScoreAnxiete(int $scoreAnxiete): self { $this->scoreAnxiete = $scoreAnxiete; return $this; }

    public function getScoreTristesse(): int { return $this->scoreTristesse; }
    public function setScoreTristesse(int $scoreTristesse): self { $this->scoreTristesse = $scoreTristesse; return $this; }

    public function getScoreColere(): int { return $this->scoreColere; }
    public function setScoreColere(int $scoreColere): self { $this->scoreColere = $scoreColere; return $this; }

    public function getScoreJoie(): int { return $this->scoreJoie; }
    public function setScoreJoie(int $scoreJoie): self { $this->scoreJoie = $scoreJoie; return $this; }


}
