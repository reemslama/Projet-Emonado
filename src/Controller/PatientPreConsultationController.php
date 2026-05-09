<?php

namespace App\Controller;

use App\Entity\Consultation;
use App\Entity\ConsultationPayment;
use App\Entity\ConsultationQuestionnaire;
use App\Entity\RendezVous;
use App\Entity\User;
use DateTimeImmutable;
use App\Service\PreConsultationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/patient/pre-consultation')]
final class PatientPreConsultationController extends AbstractController
{
    #[Route('', name: 'patient_pre_consultation', methods: ['GET'])]
    public function index(
        Request $request,
        PreConsultationService $preConsultationService,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_PATIENT');

        $patient = $this->getUser();
        if (!$patient instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $allRows = $preConsultationService->buildRowsForPatient($patient);
        $hasAcceptedRdv = $allRows !== [];

        $paymentFilter = (string) $request->query->get('paiement', 'tous');
        $paymentFilter = in_array($paymentFilter, ['tous', 'pending', 'paid'], true) ? $paymentFilter : 'tous';

        $sort = (string) $request->query->get('tri', 'recent');
        $sort = $sort === 'ancien' ? 'ancien' : 'recent';

        $rows = $allRows;
        if ($sort === 'ancien') {
            $rows = array_reverse($rows);
        }

        if ($paymentFilter !== 'tous') {
            $rows = array_values(array_filter($rows, static function (array $row) use ($paymentFilter): bool {
                /** @var ConsultationPayment $p */
                $p = $row['payment'];
                if ($paymentFilter === 'paid') {
                    return $p->isPaid();
                }

                return !$p->isPaid();
            }));
        }

        $selectedId = $request->query->getInt('c', 0);
        $selected = null;
        $questionnaire = null;

        if ($selectedId > 0) {
            foreach ($rows as $row) {
                /** @var Consultation $c */
                $c = $row['consultation'];
                if ($c->getId() === $selectedId) {
                    $selected = $row;
                    break;
                }
            }
        }
        if ($selected === null && $rows !== []) {
            $selected = $rows[0];
        }
        if ($selected !== null) {
            $questionnaire = $selected['questionnaire'] ?? null;
        }

        return $this->render('patient/pre_consultation.html.twig', [
            'rows' => $rows,
            'selected' => $selected,
            'questionnaire' => $questionnaire,
            'payment_filter' => $paymentFilter,
            'sort' => $sort,
            'has_accepted_rdv' => $hasAcceptedRdv,
        ]);
    }

    #[Route('/payer', name: 'patient_pre_consultation_pay', methods: ['POST'])]
    public function payLocal(
        Request $request,
        PreConsultationService $preConsultationService,
        EntityManagerInterface $em,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_PATIENT');

        $patient = $this->getUser();
        if (!$patient instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $cid = $request->request->getInt('consultation_id', 0);
        if ($cid <= 0) {
            $this->addFlash('error', 'Consultation invalide.');
            return $this->redirectToRoute('patient_pre_consultation');
        }

        if (!$this->isCsrfTokenValid('patient_pay_' . $cid, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Session expirée, veuillez réessayer.');
            return $this->redirectToRoute('patient_pre_consultation', ['c' => $cid]);
        }

        $consultation = $em->getRepository(Consultation::class)->find($cid);
        if (!$consultation instanceof Consultation) {
            $this->addFlash('error', 'Consultation introuvable.');
            return $this->redirectToRoute('patient_pre_consultation');
        }

        try {
            $preConsultationService->assertConsultationOwnedByPatient($consultation, $patient);
        } catch (\InvalidArgumentException) {
            throw $this->createAccessDeniedException();
        }

        $rdv = $preConsultationService->resolveRendezVousForPrepConsultation($consultation);
        if (!$rdv instanceof RendezVous) {
            $this->addFlash('error', 'Rendez-vous associé introuvable pour cette consultation.');
            return $this->redirectToRoute('patient_pre_consultation', ['c' => $cid]);
        }

        $payment = $preConsultationService->ensurePayment($consultation, $rdv);
        if ($payment->isPaid()) {
            $this->addFlash('info', 'Ce rendez-vous est déjà payé.');
            return $this->redirectToRoute('patient_pre_consultation', ['c' => $cid]);
        }

        $payment->setStatut(ConsultationPayment::STATUT_PAID);
        $payment->setPaidAt(new DateTimeImmutable());
        $payment->setStripeSessionId('local_simule');
        $payment->setReferenceLocale('LOCAL-' . bin2hex(random_bytes(4)));
        $em->flush();

        $this->addFlash('success', 'Paiement enregistré (simulation locale). Vous pouvez remplir le formulaire pré-consultation.');
        return $this->redirectToRoute('patient_pre_consultation', ['c' => $cid]);
    }

    #[Route('/questionnaire', name: 'patient_pre_consultation_questionnaire', methods: ['POST'])]
    public function saveQuestionnaire(
        Request $request,
        PreConsultationService $preConsultationService,
        EntityManagerInterface $em,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_PATIENT');

        $patient = $this->getUser();
        if (!$patient instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $cid = $request->request->getInt('consultation_id', 0);
        if ($cid <= 0) {
            $this->addFlash('error', 'Consultation invalide.');
            return $this->redirectToRoute('patient_pre_consultation');
        }

        if (!$this->isCsrfTokenValid('patient_questionnaire_' . $cid, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Session expirée, veuillez réessayer.');
            return $this->redirectToRoute('patient_pre_consultation', ['c' => $cid]);
        }

        $consultation = $em->getRepository(Consultation::class)->find($cid);
        if (!$consultation instanceof Consultation) {
            $this->addFlash('error', 'Consultation introuvable.');
            return $this->redirectToRoute('patient_pre_consultation');
        }

        try {
            $preConsultationService->assertConsultationOwnedByPatient($consultation, $patient);
        } catch (\InvalidArgumentException) {
            throw $this->createAccessDeniedException();
        }

        $rdv = $preConsultationService->resolveRendezVousForPrepConsultation($consultation);
        if (!$rdv instanceof RendezVous) {
            $this->addFlash('error', 'Rendez-vous associé introuvable pour cette consultation.');
            return $this->redirectToRoute('patient_pre_consultation', ['c' => $cid]);
        }

        $payment = $preConsultationService->ensurePayment($consultation, $rdv);
        if (!$payment->isPaid()) {
            $this->addFlash('error', 'Le formulaire est disponible après paiement.');
            return $this->redirectToRoute('patient_pre_consultation', ['c' => $cid]);
        }

        $motif = trim((string) $request->request->get('motif_principal', ''));
        $symptomes = trim((string) $request->request->get('symptomes_observes', ''));
        if ($motif === '' || $symptomes === '') {
            $this->addFlash('error', 'Le motif principal et les symptômes sont obligatoires.');
            return $this->redirectToRoute('patient_pre_consultation', ['c' => $cid]);
        }

        $q = $em->getRepository(ConsultationQuestionnaire::class)->findOneBy(['payment' => $payment]);
        if (!$q instanceof ConsultationQuestionnaire) {
            $q = new ConsultationQuestionnaire();
            $q->setPayment($payment);
            $q->setRendezVous($rdv);
            $q->setPatient($patient);
            $psyQ = $rdv->getDisponibilite()?->getPsychologue() ?? $consultation->getPsychologue();
            if (!$psyQ instanceof User) {
                $this->addFlash('error', 'Psychologue introuvable pour enregistrer le formulaire.');
                return $this->redirectToRoute('patient_pre_consultation', ['c' => $cid]);
            }
            $q->setPsychologue($psyQ);
        }

        $q->setMotifPrincipal($motif);
        $q->setSymptomesObserves($symptomes);
        $q->setNoteVocaleTranscrite(trim((string) $request->request->get('note_vocale_transcrite', '')) ?: null);
        $q->setStress($this->intScale($request->request->get('stress')));
        $q->setAnxiete($this->intScale($request->request->get('anxiete')));
        $q->setHumeur($this->intScale($request->request->get('humeur')));
        $q->setSommeil($this->intScale($request->request->get('sommeil')));
        $q->setEnergie($this->intScale($request->request->get('energie')));
        $q->setSoutien($this->intScale($request->request->get('soutien')));
        $q->setUrgenceRessentie($this->intScale($request->request->get('urgence_ressentie')));
        $q->setRisqueAutoAgression(trim((string) $request->request->get('risque_auto_agression', 'none')) ?: 'none');
        $q->setContexteComplementaire(trim((string) $request->request->get('contexte_complementaire', '')) ?: null);

        $em->persist($q);
        $em->flush();

        $this->addFlash('success', 'Formulaire pré-consultation enregistré.');
        return $this->redirectToRoute('patient_pre_consultation', ['c' => $cid]);
    }

    private function int0to10(mixed $v): ?int
    {
        if ($v === null || $v === '') {
            return null;
        }
        $i = (int) $v;
        if ($i < 0) {
            $i = 0;
        }
        if ($i > 10) {
            $i = 10;
        }

        return $i;
    }

    private function intScale(mixed $v, int $default = 5): int
    {
        return $this->int0to10($v) ?? $default;
    }
}
