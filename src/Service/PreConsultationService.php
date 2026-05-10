<?php

namespace App\Service;

use App\Entity\Consultation;
use App\Entity\ConsultationPayment;
use App\Entity\ConsultationQuestionnaire;
use App\Entity\DossierMedical;
use App\Entity\RendezVous;
use App\Entity\User;
use App\Repository\ConsultationPaymentRepository;
use App\Repository\ConsultationQuestionnaireRepository;
use App\Repository\DossierMedicalRepository;
use App\Repository\RendezVousRepository;
use Doctrine\ORM\EntityManagerInterface;
use DateTimeImmutable;

final class PreConsultationService
{
    public const DEFAULT_MONTANT = '50.00';

    public function __construct(
        private readonly RendezVousRepository $rendezVousRepository,
        private readonly DossierMedicalRepository $dossierMedicalRepository,
        private readonly ConsultationPaymentRepository $consultationPaymentRepository,
        private readonly ConsultationQuestionnaireRepository $consultationQuestionnaireRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function getOrCreateDossier(User $patient): DossierMedical
    {
        $dossier = $this->dossierMedicalRepository->findOneBy(['patient' => $patient]);
        if ($dossier instanceof DossierMedical) {
            return $dossier;
        }
        $dossier = new DossierMedical();
        $dossier->setPatient($patient);
        $dossier->assignCreator($patient);
        $dossier->assignUpdater($patient);
        $this->em->persist($dossier);
        $this->em->flush();

        return $dossier;
    }

    /**
     * Rendez-vous considérés comme acceptés : même règle que la liste patient
     * (`RendezVous::normalizeStatut`), pas une égalité SQL stricte sur `accepte`,
     * car la base peut contenir des variantes (accents, casse, libellés proches).
     *
     * @return list<RendezVous>
     */
    public function findAcceptedRendezVous(User $patient): array
    {
        $rdvs = $this->rendezVousRepository->findHistoriqueByPatient($patient);

        return array_values(array_filter(
            $rdvs,
            static function (RendezVous $r): bool {
                return RendezVous::normalizeStatut($r->getStatut()) === 'accepte';
            },
        ));
    }

    public function prepTag(RendezVous $rdv): string
    {
        return 'PRECONSULT_RDV_' . $rdv->getId();
    }

    public function findOrCreatePrepConsultation(DossierMedical $dossier, RendezVous $rdv): Consultation
    {
        $tag = $this->prepTag($rdv);
        foreach ($dossier->getConsultations() as $c) {
            if ($c->getSujetAborde() === $tag) {
                return $c;
            }
        }

        $c = new Consultation();
        $c->setDossier($dossier);
        $patient = $dossier->getPatient();
        if ($patient instanceof User) {
            $c->assignCreator($patient);
            $c->assignUpdater($patient);
        }
        $dt = $rdv->getDate();
        if ($dt instanceof \DateTimeInterface) {
            $c->setDate(\DateTime::createFromImmutable(\DateTimeImmutable::createFromInterface($dt)));
        } else {
            $c->setDate(new \DateTime('today'));
        }
        $c->setPsychologue($rdv->getDisponibilite()?->getPsychologue());
        $c->setSujetAborde($tag);
        $c->setCompteRendu(null);
        $this->em->persist($c);
        $this->em->flush();

        return $c;
    }

    public function resolveRendezVousForPrepConsultation(Consultation $consultation): ?RendezVous
    {
        $tag = (string) ($consultation->getSujetAborde() ?? '');
        if (preg_match('/^PRECONSULT_RDV_(\d+)$/', $tag, $m)) {
            return $this->rendezVousRepository->find((int) $m[1]);
        }

        return null;
    }

    public function ensurePayment(Consultation $consultation, RendezVous $rdv): ConsultationPayment
    {
        $existing = $this->consultationPaymentRepository->findOneBy(['consultation' => $consultation]);
        if (!$existing instanceof ConsultationPayment) {
            $existing = $this->consultationPaymentRepository->findOneBy(['rendezVous' => $rdv]);
        }
        if ($existing instanceof ConsultationPayment) {
            if ($existing->getConsultation()?->getId() !== $consultation->getId()) {
                $existing->setConsultation($consultation);
                $this->em->flush();
            }

            return $existing;
        }

        $patient = $rdv->getPatient();
        if (!$patient instanceof User) {
            throw new \InvalidArgumentException('Patient manquant sur le rendez-vous.');
        }
        $psy = $rdv->getDisponibilite()?->getPsychologue() ?? $consultation->getPsychologue();
        if (!$psy instanceof User) {
            throw new \InvalidArgumentException('Psychologue manquant pour le paiement.');
        }

        $p = new ConsultationPayment();
        $p->setRendezVous($rdv);
        $p->setConsultation($consultation);
        $p->setPatient($patient);
        $p->setPsychologue($psy);
        $p->setMontant(self::DEFAULT_MONTANT);
        $p->setDevise('TND');
        $p->setStatut(ConsultationPayment::STATUT_PENDING);
        $p->setStripeSessionId('local');
        $this->em->persist($p);
        $this->em->flush();

        return $p;
    }

    /**
     * @return list<array{rdv: RendezVous, consultation: Consultation, payment: ConsultationPayment, questionnaire: ?ConsultationQuestionnaire}>
     */
    public function buildRowsForPatient(User $patient): array
    {
        $dossier = $this->getOrCreateDossier($patient);
        $out = [];
        foreach ($this->findAcceptedRendezVous($patient) as $rdv) {
            $consultation = $this->findOrCreatePrepConsultation($dossier, $rdv);
            $payment = $this->ensurePayment($consultation, $rdv);
            $questionnaire = $this->consultationQuestionnaireRepository->findOneBy(['payment' => $payment]);
            $out[] = [
                'rdv' => $rdv,
                'consultation' => $consultation,
                'payment' => $payment,
                'questionnaire' => $questionnaire,
            ];
        }

        return $out;
    }

    public function assertConsultationOwnedByPatient(Consultation $consultation, User $patient): void
    {
        $owner = $consultation->getDossier()?->getPatient();
        if (!$owner instanceof User || $owner->getId() !== $patient->getId()) {
            throw new \InvalidArgumentException('Consultation inaccessible.');
        }
    }
}
