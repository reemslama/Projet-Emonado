<?php

namespace App\Command;

use App\Entity\RendezVous;
use App\Repository\RendezVousRepository;
use Doctrine\DBAL\Types\Types;
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
    private RendezVousRepository $rdvRepo;
    private MailjetSmsService $smsService;

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

        $demain = new \DateTimeImmutable('tomorrow');

        $rdvs = $this->rdvRepo->createQueryBuilder('r')
            ->join('r.disponibilite', 'd')
            ->addSelect('d')
            ->andWhere('d.date = :demain')
            ->andWhere('r.statut = :st')
            ->setParameter('demain', $demain, Types::DATE_MUTABLE)
            ->setParameter('st', RendezVous::STATUT_ACCEPTE)
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

            $rdvDate = $rdv->getDate();
            if (!$patient || !$patient->getTelephone()) {
                $label = $rdvDate ? $rdvDate->format('d/m/Y') : '?';
                $io->warning("⚠️ Patient sans téléphone (RDV {$label})");
                continue;
            }

            $dateFormatee = $rdvDate ? $rdvDate->format('d/m/Y à H\hi') : '';
            
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
