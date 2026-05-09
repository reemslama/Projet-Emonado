<?php

namespace App\Service;

use App\Entity\ConsultationPayment;
use App\Entity\ConsultationQuestionnaire;
use App\Entity\RendezVous;
use App\Entity\User;
use App\Repository\ConsultationPaymentRepository;
use App\Repository\ConsultationQuestionnaireRepository;
use App\Repository\RendezVousRepository;

final class PsychologuePreparationService
{
    public function __construct(
        private readonly RendezVousRepository $rendezVousRepository,
        private readonly ConsultationPaymentRepository $consultationPaymentRepository,
        private readonly ConsultationQuestionnaireRepository $consultationQuestionnaireRepository,
        private readonly ConsultationPrepPredictionService $predictionService,
    ) {
    }

    /**
     * @return list<array{rdv: RendezVous, payment: ?ConsultationPayment, questionnaire: ?ConsultationQuestionnaire, prediction: array}>
     */
    public function buildRowsForPsychologue(User $psychologue): array
    {
        /** @var list<RendezVous> $candidates */
        $candidates = $this->rendezVousRepository->createQueryBuilder('r')
            ->leftJoin('r.disponibilite', 'd')->addSelect('d')
            ->leftJoin('r.patient', 'p')->addSelect('p')
            ->leftJoin('r.type', 't')->addSelect('t')
            ->andWhere('d.psychologue = :psy')
            ->setParameter('psy', $psychologue)
            ->orderBy('d.date', 'DESC')
            ->addOrderBy('d.heureDebut', 'DESC')
            ->getQuery()
            ->getResult();

        $accepted = array_values(array_filter(
            $candidates,
            static function (RendezVous $r): bool {
                return RendezVous::normalizeStatut($r->getStatut()) === 'accepte';
            },
        ));

        if ($accepted === []) {
            return [];
        }

        $rdvIds = [];
        foreach ($accepted as $rdv) {
            $id = $rdv->getId();
            if ($id !== null) {
                $rdvIds[] = $id;
            }
        }

        /** @var list<ConsultationPayment> $payments */
        $payments = $this->consultationPaymentRepository->createQueryBuilder('pay')
            ->andWhere('pay.rendezVous IN (:ids)')
            ->setParameter('ids', $rdvIds)
            ->getQuery()
            ->getResult();

        $paymentByRdv = [];
        foreach ($payments as $pay) {
            $rid = $pay->getRendezVous()?->getId();
            if ($rid !== null) {
                $paymentByRdv[$rid] = $pay;
            }
        }

        $paymentIds = [];
        foreach ($payments as $pay) {
            $pid = $pay->getId();
            if ($pid !== null) {
                $paymentIds[] = $pid;
            }
        }

        $questionnaireByPaymentId = [];
        if ($paymentIds !== []) {
            /** @var list<ConsultationQuestionnaire> $questionnaires */
            $questionnaires = $this->consultationQuestionnaireRepository->createQueryBuilder('q')
                ->andWhere('q.payment IN (:pids)')
                ->setParameter('pids', $paymentIds)
                ->getQuery()
                ->getResult();
            foreach ($questionnaires as $qq) {
                $qpid = $qq->getPayment()?->getId();
                if ($qpid !== null) {
                    $questionnaireByPaymentId[$qpid] = $qq;
                }
            }
        }

        $out = [];
        foreach ($accepted as $rdv) {
            $rid = $rdv->getId();
            $payment = $rid !== null ? ($paymentByRdv[$rid] ?? null) : null;
            $questionnaire = null;
            if ($payment instanceof ConsultationPayment) {
                $pid = $payment->getId();
                $questionnaire = $pid !== null ? ($questionnaireByPaymentId[$pid] ?? null) : null;
            }

            $prediction = $this->predictionService->analyze($questionnaire);
            $out[] = [
                'rdv' => $rdv,
                'payment' => $payment,
                'questionnaire' => $questionnaire,
                'prediction' => $prediction,
            ];
        }

        return $out;
    }

    /**
     * @param list<array{rdv: RendezVous, payment: ?ConsultationPayment, questionnaire: ?ConsultationQuestionnaire, prediction: array}> $rows
     * @return list<array{rdv: RendezVous, payment: ?ConsultationPayment, questionnaire: ?ConsultationQuestionnaire, prediction: array}>
     */
    public function filterAndSortRows(array $rows, ?int $patientIdFilter, string $sort, string $search): array
    {
        $searchNorm = mb_strtolower(trim($search));

        $filtered = array_values(array_filter($rows, static function (array $row) use ($patientIdFilter, $searchNorm): bool {
            /** @var RendezVous $rdv */
            $rdv = $row['rdv'];
            $patient = $rdv->getPatient();
            if ($patientIdFilter !== null && (!$patient instanceof User || $patient->getId() !== $patientIdFilter)) {
                return false;
            }
            if ($searchNorm === '') {
                return true;
            }
            $nom = mb_strtolower(trim((string) $patient?->getNom() . ' ' . (string) $patient?->getPrenom()));
            $type = mb_strtolower(trim((string) ($rdv->getType()?->getLibelle() ?? '')));
            $dateStr = '';
            if ($rdv->getDate() instanceof \DateTimeInterface) {
                $dateStr = mb_strtolower($rdv->getDate()->format('d/m/Y H:i'));
            }
            return str_contains($nom, $searchNorm)
                || str_contains($type, $searchNorm)
                || str_contains($dateStr, $searchNorm);
        }));

        usort($filtered, static function (array $a, array $b) use ($sort): int {
            $rdvA = $a['rdv'];
            $rdvB = $b['rdv'];
            $tsA = $rdvA->getDate() instanceof \DateTimeInterface ? $rdvA->getDate()->getTimestamp() : 0;
            $tsB = $rdvB->getDate() instanceof \DateTimeInterface ? $rdvB->getDate()->getTimestamp() : 0;

            if ($sort === 'ancien') {
                return $tsA <=> $tsB;
            }
            if ($sort === 'grave') {
                $rankA = (int) ($a['prediction']['severity_rank'] ?? 0);
                $rankB = (int) ($b['prediction']['severity_rank'] ?? 0);
                if ($rankA !== $rankB) {
                    return $rankB <=> $rankA;
                }

                return $tsB <=> $tsA;
            }

            return $tsB <=> $tsA;
        });

        return $filtered;
    }

    /**
     * @param list<array{rdv: RendezVous, payment: ?ConsultationPayment, questionnaire: ?ConsultationQuestionnaire, prediction: array}> $allRows
     *
     * @return array{followed: int, paid: int, forms: int}
     */
    public function countStats(array $allRows): array
    {
        $followed = count($allRows);
        $paid = 0;
        $forms = 0;
        foreach ($allRows as $row) {
            $pay = $row['payment'];
            if ($pay instanceof ConsultationPayment && $pay->isPaid()) {
                ++$paid;
            }
            if ($row['questionnaire'] instanceof ConsultationQuestionnaire) {
                ++$forms;
            }
        }

        return ['followed' => $followed, 'paid' => $paid, 'forms' => $forms];
    }

    /**
     * @param list<array{rdv: RendezVous, payment: ?ConsultationPayment, questionnaire: ?ConsultationQuestionnaire, prediction: array}> $allRows
     *
     * @return list<User>
     */
    public function distinctPatients(array $allRows): array
    {
        /** @var array<int, User> $byId */
        $byId = [];
        foreach ($allRows as $row) {
            $p = $row['rdv']->getPatient();
            if ($p instanceof User && $p->getId() !== null) {
                $byId[$p->getId()] = $p;
            }
        }

        $list = array_values($byId);
        usort($list, static function (User $a, User $b): int {
            return strcasecmp(trim((string) $a->getNom() . ' ' . (string) $a->getPrenom()), trim((string) $b->getNom() . ' ' . (string) $b->getPrenom()));
        });

        return $list;
    }
}
