<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:create-default-users',
    description: 'Créer admin et psychologue par défaut'
)]
class CreateDefaultUsersCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Admin
        $admin = new User();
        $admin->setEmail('admin@emonaso.com');
        $admin->setPassword($this->hasher->hashPassword($admin, 'adminpass'));
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setNom('Admin');
        $admin->setPrenom('System');
        $this->em->persist($admin);

        // Psychologue
        $psy = new User();
        $psy->setEmail('psy@emonaso.com');
        $psy->setPassword($this->hasher->hashPassword($psy, 'Psy123'));
        $psy->setRoles(['ROLE_PSYCHOLOGUE']);
        $psy->setNom('Jean');
        $psy->setPrenom('Dupont');
        $psy->setSpecialite('Psychologie clinique');
        $this->em->persist($psy);

        $this->em->flush();

        $output->writeln('Admin et psychologue créés avec succès !');
        return Command::SUCCESS;
    }
}
