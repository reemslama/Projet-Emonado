<?php

namespace App\Entity;

use App\Repository\ConsultationDocumentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\TimestampableTrait;
use App\Entity\Traits\BlameableTrait;

#[ORM\Entity(repositoryClass: ConsultationDocumentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ConsultationDocument
{
    use TimestampableTrait;
    use BlameableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $typeFichier = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $pathOrUrl = null;

    #[ORM\ManyToOne(targetEntity: Consultation::class, inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Consultation $consultation = null;

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getTypeFichier(): ?string
    {
        return $this->typeFichier;
    }

    public function setTypeFichier(?string $typeFichier): self
    {
        $this->typeFichier = $typeFichier;
        return $this;
    }

    public function getPathOrUrl(): ?string
    {
        return $this->pathOrUrl;
    }

    public function setPathOrUrl(?string $pathOrUrl): self
    {
        $this->pathOrUrl = $pathOrUrl;
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
}
