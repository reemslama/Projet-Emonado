<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column]
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

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $specialite = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $faceIdImagePath = null;

    #[ORM\Column(nullable: true)]
    private ?bool $hasChild = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $resetPasswordToken = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $resetPasswordTokenExpiresAt = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'patients')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $psychologue = null;

    #[ORM\OneToMany(mappedBy: 'psychologue', targetEntity: self::class)]
    private Collection $patients;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Journal::class, orphanRemoval: true)]
    private Collection $journals;

    public function __construct()
    {
        $this->patients = new ArrayCollection();
        $this->journals = new ArrayCollection();
    }

    // ================= SECURITY =================

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
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

    // ================= GETTERS & SETTERS =================

    public function getId(): ?int { return $this->id; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }

    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): self { $this->password = $password; return $this; }

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

    public function getAvatar(): ?string { return $this->avatar; }
    public function setAvatar(?string $avatar): self { $this->avatar = $avatar; return $this; }

    public function getFaceIdImagePath(): ?string { return $this->faceIdImagePath; }
    public function setFaceIdImagePath(?string $faceIdImagePath): self { $this->faceIdImagePath = $faceIdImagePath; return $this; }

    public function isHasChild(): ?bool { return $this->hasChild; }
    public function setHasChild(?bool $hasChild): self { $this->hasChild = $hasChild; return $this; }

    public function getResetPasswordToken(): ?string { return $this->resetPasswordToken; }
    public function setResetPasswordToken(?string $resetPasswordToken, ?\DateTimeImmutable $expiresAt = null): self
    {
        $this->resetPasswordToken = $resetPasswordToken;
        $this->resetPasswordTokenExpiresAt = $expiresAt;
        return $this;
    }

    public function getResetPasswordTokenExpiresAt(): ?\DateTimeImmutable { return $this->resetPasswordTokenExpiresAt; }
    public function clearResetPasswordToken(): self
    {
        $this->resetPasswordToken = null;
        $this->resetPasswordTokenExpiresAt = null;
        return $this;
    }

    // ================= RELATION PSYCHOLOGUE =================

    public function getPsychologue(): ?self { return $this->psychologue; }
    public function setPsychologue(?self $psychologue): self { $this->psychologue = $psychologue; return $this; }

    public function getPatients(): Collection { return $this->patients; }

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

    // ================= JOURNALS =================

    public function getJournals(): Collection { return $this->journals; }

    public function addJournal(Journal $journal): self
    {
        if (!$this->journals->contains($journal)) {
            $this->journals->add($journal);
            $journal->setUser($this);
        }
        return $this;
    }

    public function removeJournal(Journal $journal): self
    {
        if ($this->journals->removeElement($journal)) {
            if ($journal->getUser() === $this) {
                $journal->setUser(null);
            }
        }
        return $this;
    }
}