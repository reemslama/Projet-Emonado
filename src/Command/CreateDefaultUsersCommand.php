<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:create-default-users',
    description: 'Créer admin et psychologue par défaut pour tester le dashboard'
)]
class CreateDefaultUsersCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
        private UserRepository $userRepo
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $created = [];

        // Admin (dashboard /admin)
        if (!$this->userRepo->findOneBy(['email' => 'admin@emonado.com'])) {
            $admin = new User();
            $admin->setEmail('admin@emonado.com');
            $admin->setPassword($this->hasher->hashPassword($admin, 'Admin123'));
            $admin->setRoles(['ROLE_ADMIN']);
            $admin->setNom('Admin');
            $admin->setPrenom('System');
            $this->em->persist($admin);
            $created[] = 'Admin (admin@emonado.com)';
        }

        // Psychologue (tableau de bord psychologue)
        if (!$this->userRepo->findOneBy(['email' => 'psy@emonado.com'])) {
            $psy = new User();
            $psy->setEmail('psy@emonado.com');
            $psy->setPassword($this->hasher->hashPassword($psy, 'Psy123'));
            $psy->setRoles(['ROLE_PSYCHOLOGUE']);
            $psy->setNom('Jean');
            $psy->setPrenom('Dupont');
            $psy->setSpecialite('Psychologie clinique');
            $psy->setTelephone('0600000000');
            $this->em->persist($psy);
            $created[] = 'Psychologue (psy@emonado.com)';
        }

        $this->em->flush();

        if (\count($created) > 0) {
            $output->writeln('Comptes créés : ' . implode(', ', $created));
        } else {
            $output->writeln('Les comptes admin et psychologue existent déjà.');
        }
        $output->writeln('');
        $output->writeln('--- Connexion pour tester ---');
        $output->writeln('  Dashboard Admin    : http://127.0.0.1:8000/admin/login');
        $output->writeln('    Email: admin@emonado.com  | Mot de passe: Admin123');
        $output->writeln('  Dashboard Psychologue : http://127.0.0.1:8000/login');
        $output->writeln('    Email: psy@emonado.com    | Mot de passe: Psy123');
        $output->writeln('');

        return Command::SUCCESS;
    }
}
