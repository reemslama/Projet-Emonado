<?php

namespace App\Command;

use App\Repository\RendezVousRepository;
use App\Service\MailjetSmsService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-rappels-sms',
    description: 'Envoie des rappels SMS pour les rendez-vous du lendemain (via Mailjet)',
)]
class SendRappelsSmsCommand extends Command
{
    private $rdvRepo;
    private $smsService;

    public function __construct(RendezVousRepository $rdvRepo, MailjetSmsService $smsService)
    {
        $this->rdvRepo = $rdvRepo;
        $this->smsService = $smsService;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('📱 Envoi des rappels SMS (Mailjet)');

        $demain = new \DateTime('+1 day');
        $debut = (clone $demain)->setTime(0, 0, 0);
        $fin   = (clone $demain)->setTime(23, 59, 59);

        $rdvs = $this->rdvRepo->createQueryBuilder('r')
            ->where('r.date BETWEEN :debut AND :fin')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getResult();

        if (empty($rdvs)) {
            $io->success('✅ Aucun rendez-vous pour demain.');
            return Command::SUCCESS;
        }

        $io->note(sprintf('📅 %d rendez-vous trouvé(s)', count($rdvs)));

        $compte = 0;
        $erreurs = 0;

        foreach ($rdvs as $rdv) {
            $patient = $rdv->getPatient();

            if (!$patient || !$patient->getTelephone()) {
                $io->warning("⚠️ Patient sans téléphone (RDV {$rdv->getDate()->format('d/m/Y')})");
                continue;
            }

            $dateFormatee = $rdv->getDate()->format('d/m/Y à H\hi');
            
            if ($this->smsService->sendRappelRdv(
                $patient->getTelephone(),
                $patient->getPrenom() . ' ' . $patient->getNom(),
                $rdv->getNomPsychologue(),
                $dateFormatee
            )) {
                $io->writeln("  ✅ SMS envoyé à {$patient->getTelephone()}");
                $compte++;
            } else {
                $io->error("❌ Échec pour {$patient->getTelephone()}");
                $erreurs++;
            }
        }

        $io->success("✅ $compte SMS envoyés" . ($erreurs ? ", $erreurs échecs" : ""));
        return Command::SUCCESS;
    }
}