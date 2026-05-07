<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Admin user
        $admin = new User();
        $admin->setEmail('admin@example.com')
            ->setNom('Admin')
            ->setPrenom('Super')
            ->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'adminpass'));
        $manager->persist($admin);

        // Psychologue user
        $psy = new User();
        $psy->setEmail('psy@example.com')
            ->setNom('Dupont')
            ->setPrenom('Psy')
            ->setRoles(['ROLE_PSYCHOLOGUE'])
            ->setSpecialite('Thérapie cognitive');
        $psy->setPassword($this->passwordHasher->hashPassword($psy, 'psypass'));
        $manager->persist($psy);

        // Patient user
        $user = new User();
        $user->setEmail('user@example.com')
            ->setNom('Doe')
            ->setPrenom('User')
            ->setRoles(['ROLE_PATIENT']);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'userpass'));
        $manager->persist($user);

        $manager->flush();
    }
}
