<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'user')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    // 🔥 COMPAT JAVA → STRING (PAS JSON)
    #[ORM\Column(length: 255)]
    private ?string $role = null;

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
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

    // ---------------- SECURITY ----------------

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function eraseCredentials(): void {}

    // 🔥 SIMPLE ROLE STRING COMPATIBLE JAVA
    public function getRoles(): array
    {
        return [$this->role ?? 'ROLE_USER'];
    }

    public function setRoles(string $role): self
    {
        $this->role = $role;
        return $this;
    }

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

    public function getSpecialite(): ?string { return $this->specialite; }
    public function setSpecialite(?string $s): self { $this->specialite = $s; return $this; }

    public function getAvatar(): ?string { return $this->avatar; }
    public function setAvatar(?string $a): self { $this->avatar = $a; return $this; }

    public function getFaceIdImagePath(): ?string { return $this->faceIdImagePath; }
    public function setFaceIdImagePath(?string $f): self { $this->faceIdImagePath = $f; return $this; }

    public function isHasChild(): ?bool { return $this->hasChild; }
    public function setHasChild(?bool $h): self { $this->hasChild = $h; return $this; }
}