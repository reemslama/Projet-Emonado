<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'test_psy_reponse')]
class TestPsyReponse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TestPsyScene::class, inversedBy: 'reponses')]
    #[ORM\JoinColumn(name: 'scene_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?TestPsyScene $scene = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $label = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $labelEn = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $labelAr = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $emoji = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imagePath = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $etatDesc = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $etatDescEn = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $etatDescAr = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $couleur = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $poidsAnxiete = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $poidsTristesse = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $poidsColere = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $poidsJoie = 0;

    // Getters and Setters
    public function getId(): ?int { return $this->id; }
    public function getScene(): ?TestPsyScene { return $this->scene; }
    public function setScene(?TestPsyScene $scene): self { $this->scene = $scene; return $this; }
    public function getLabel(): ?string { return $this->label; }
    public function setLabel(?string $label): self { $this->label = $label; return $this; }
    public function getLabelEn(): ?string { return $this->labelEn; }
    public function setLabelEn(?string $labelEn): self { $this->labelEn = $labelEn; return $this; }
    public function getLabelAr(): ?string { return $this->labelAr; }
    public function setLabelAr(?string $labelAr): self { $this->labelAr = $labelAr; return $this; }
    public function getEmoji(): ?string { return $this->emoji; }
    public function setEmoji(?string $emoji): self { $this->emoji = $emoji; return $this; }
    public function getImagePath(): ?string { return $this->imagePath; }
    public function setImagePath(?string $imagePath): self { $this->imagePath = $imagePath; return $this; }
    public function getEtatDesc(): ?string { return $this->etatDesc; }
    public function setEtatDesc(?string $etatDesc): self { $this->etatDesc = $etatDesc; return $this; }
    public function getEtatDescEn(): ?string { return $this->etatDescEn; }
    public function setEtatDescEn(?string $etatDescEn): self { $this->etatDescEn = $etatDescEn; return $this; }
    public function getEtatDescAr(): ?string { return $this->etatDescAr; }
    public function setEtatDescAr(?string $etatDescAr): self { $this->etatDescAr = $etatDescAr; return $this; }
    public function getCouleur(): ?string { return $this->couleur; }
    public function setCouleur(?string $couleur): self { $this->couleur = $couleur; return $this; }
    public function getPoidsAnxiete(): int { return $this->poidsAnxiete; }
    public function setPoidsAnxiete(int $poidsAnxiete): self { $this->poidsAnxiete = $poidsAnxiete; return $this; }
    public function getPoidsTristesse(): int { return $this->poidsTristesse; }
    public function setPoidsTristesse(int $poidsTristesse): self { $this->poidsTristesse = $poidsTristesse; return $this; }
    public function getPoidsColere(): int { return $this->poidsColere; }
    public function setPoidsColere(int $poidsColere): self { $this->poidsColere = $poidsColere; return $this; }
    public function getPoidsJoie(): int { return $this->poidsJoie; }
    public function setPoidsJoie(int $poidsJoie): self { $this->poidsJoie = $poidsJoie; return $this; }
}
