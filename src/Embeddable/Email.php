<?php
namespace App\Embeddable;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class Email
{
    #[ORM\Column(name: 'email', length: 180, unique: true, nullable: true)]
    private ?string $address = null;

    public function __construct(?string $address = null)
    {
        $this->address = $address;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->address;
    }
}
