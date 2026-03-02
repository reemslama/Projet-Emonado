<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
<<<<<<< HEAD
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private ?string $email = null;
=======
use App\Embeddable\Email as EmailValue;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private ?UuidV7 $id = null;

    #[ORM\Embedded(class: EmailValue::class, columnPrefix: false)]
    private EmailValue $email;
>>>>>>> d9465e5 (finalVersionByTeam)

    #[ORM\Column(type: 'json')]
    private array $roles = [];

<<<<<<< HEAD
    #[ORM\Column(type: 'string')]
=======
    #[ORM\Column(nullable: true)]
    #[Ignore]
>>>>>>> d9465e5 (finalVersionByTeam)
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $prenom = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $sexe = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateNaissance = null;

    // ✅ AJOUT IMPORTANT (Java compatible)
    #[ORM\Column(length: 255, nullable: true)]
<<<<<<< HEAD
    private ?string $role = null;

    #[ORM\Column(nullable: true)]
    private ?bool $hasChild = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $specialite = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $faceIdImagePath = null;

    // ---------------- SECURITY ----------------
=======
    private ?string $specialite = null;

    #[ORM\Column(length: 64, nullable: true)]
    #[Ignore]
    private ?string $resetPasswordToken = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Ignore]
    private ?\DateTimeImmutable $resetPasswordTokenExpiresAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notesProchaineConsultation = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Journal::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $journals;

    #[ORM\OneToMany(mappedBy: 'patient', targetEntity: RendezVous::class)]
    private Collection $rendezVouses;

    // Relation Psychologue ↔ Patients
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'patients')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $psychologue = null;

    #[ORM\OneToMany(mappedBy: 'psychologue', targetEntity: self::class)]
    private Collection $patients;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->email = new EmailValue();
        $this->journals = new ArrayCollection();
        $this->rendezVouses = new ArrayCollection();
        $this->patients = new ArrayCollection();
    }

    // ================= SECURITY =================
>>>>>>> d9465e5 (finalVersionByTeam)

    public function getUserIdentifier(): string
    {
        return (string) $this->email->getAddress();
    }

    public function eraseCredentials(): void {}

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

<<<<<<< HEAD
    // ---------------- GETTERS / SETTERS ----------------

    public function getId(): ?int { return $this->id; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }

    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): self { $this->password = $password; return $this; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): self { $this->nom = $nom; return $this; }

    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(string $prenom): self { $this->prenom = $prenom; return $this; }

    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $t): self { $this->telephone = $t; return $this; }

    public function getSexe(): ?string { return $this->sexe; }
    public function setSexe(?string $s): self { $this->sexe = $s; return $this; }

    public function getDateNaissance(): ?\DateTimeInterface { return $this->dateNaissance; }
    public function setDateNaissance(?\DateTimeInterface $d): self { $this->dateNaissance = $d; return $this; }

    public function getRole(): ?string { return $this->role; }
    public function setRole(?string $role): self { $this->role = $role; return $this; }

    public function getHasChild(): ?bool { return $this->hasChild; }
    public function setHasChild(?bool $h): self { $this->hasChild = $h; return $this; }

    public function getSpecialite(): ?string { return $this->specialite; }
    public function setSpecialite(?string $s): self { $this->specialite = $s; return $this; }

    public function getAvatar(): ?string { return $this->avatar; }
    public function setAvatar(?string $a): self { $this->avatar = $a; return $this; }

    public function getFaceIdImagePath(): ?string { return $this->faceIdImagePath; }
    public function setFaceIdImagePath(?string $f): self { $this->faceIdImagePath = $f; return $this; }
}
=======
    // ================= GETTERS & SETTERS =================

    public function getId(): ?UuidV7 { return $this->id; }

    public function getEmail(): ?string { return $this->email->getAddress(); }
    public function setEmail(?string $email): self { $this->email->setAddress($email); return $this; }

    #[Ignore]
    public function getPassword(): ?string { return $this->password; }
    public function setPassword(#[\SensitiveParameter] ?string $password): self { $this->password = $password; return $this; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(?string $nom): self { $this->nom = $nom; return $this; }

    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(?string $prenom): self { $this->prenom = $prenom; return $this; }

    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $telephone): self { $this->telephone = $telephone; return $this; }

    public function getSexe(): ?string { return $this->sexe; }
    public function setSexe(?string $sexe): self { $this->sexe = $sexe; return $this; }

    public function getDateNaissance(): ?\DateTimeInterface { return $this->dateNaissance; }
    public function setDateNaissance(?\DateTimeInterface $dateNaissance): self { $this->dateNaissance = $dateNaissance; return $this; }

    public function getSpecialite(): ?string { return $this->specialite; }
    public function setSpecialite(?string $specialite): self { $this->specialite = $specialite; return $this; }

    #[Ignore]
    public function getResetPasswordToken(): ?string { return $this->resetPasswordToken; }
    public function setResetPasswordToken(#[\SensitiveParameter] ?string $resetPasswordToken, ?\DateTimeImmutable $expiresAt = null): self
    {
        $this->resetPasswordToken = $resetPasswordToken;
        $this->resetPasswordTokenExpiresAt = $expiresAt;
        return $this;
    }

    #[Ignore]
    public function getResetPasswordTokenExpiresAt(): ?\DateTimeImmutable { return $this->resetPasswordTokenExpiresAt; }
    public function clearResetPasswordToken(): self
    {
        $this->resetPasswordToken = null;
        $this->resetPasswordTokenExpiresAt = null;
        return $this;
    }

    public function getNotesProchaineConsultation(): ?string
    {
        return $this->notesProchaineConsultation;
    }

    public function setNotesProchaineConsultation(?string $notesProchaineConsultation): self
    {
        $this->notesProchaineConsultation = $notesProchaineConsultation;
        return $this;
    }

    // ================= RELATION PSYCHOLOGUE =================

    public function getPsychologue(): ?self
    {
        return $this->psychologue;
    }

    public function setPsychologue(?self $psychologue): self
    {
        $this->psychologue = $psychologue;
        return $this;
    }

    public function getPatients(): Collection
    {
        return $this->patients;
    }

    public function addPatient(User $patient): self
    {
        if (!$this->patients->contains($patient)) {
            $this->patients->add($patient);
            $patient->setPsychologue($this);
        }
        return $this;
    }

    public function removePatient(User $patient): self
    {
        if ($this->patients->removeElement($patient)) {
            if ($patient->getPsychologue() === $this) {
                $patient->setPsychologue(null);
            }
        }
        return $this;
    }
}
>>>>>>> d9465e5 (finalVersionByTeam)
