<?php
namespace App\Service;

use App\Entity\User;

class UserManager
{
    public function validate(User $user): bool
    {
        if (empty($user->getEmail())) {
            throw new \InvalidArgumentException('Email obligatoire');
        }
        if (!filter_var($user->getEmail(), FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email invalide');
        }
        if (empty($user->getNom())) {
            throw new \InvalidArgumentException('Le nom est obligatoire');
        }
        if (empty($user->getPassword())) {
            throw new \InvalidArgumentException('Le mot de passe est obligatoire');
        }
        return true;
    }
}