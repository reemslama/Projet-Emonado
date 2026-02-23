<?php

namespace App\Controller;

use App\Entity\AuditLog;
use App\Entity\Consultation;
use App\Entity\ConsultationDocument;
use App\Entity\DossierMedical;
use App\Entity\Prescription;
use App\Repository\DossierMedicalRepository;
use App\Repository\JournalRepository;
use App\Repository\UserRepository;
use App\Service\PredictionEvolutionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DossierMedicalController extends AbstractController
{
    #[Route('/patient/dossier', name: 'patient_dossier')]
    public function patientView(DossierMedicalRepository $dossierRepo, JournalRepository $journalRepo): Response
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_PATIENT', $user->getRoles())) {
            return $this->redirectToRoute('app_login');
        }

        $dossier = $dossierRepo->findByPatient($user->getId());
        if (!$dossier) {
            return $this->redirectToRoute('patient_consultations');
        }
        $consultationsSorted = $dossier->getConsultations()->toArray();
        usort($consultationsSorted, fn($a, $b) => ($b->getDate() ?? new \DateTime('1970-01-01')) <=> ($a->getDate() ?? new \DateTime('1970-01-01')));
        $derniereConsultation = $consultationsSorted[0] ?? null;
        $psychologuesIds = [];
        foreach ($dossier->getConsultations() as $c) {
            if ($c->getPsychologue() && !in_array($c->getPsychologue()->getId(), $psychologuesIds)) {
                $psychologuesIds[] = $c->getPsychologue()->getId();
            }
        }
        $nombrePsychologues = count($psychologuesIds);

        // Graphique d'évolution des humeurs (journal) + message simplifié
        $chartEvolution = $journalRepo->getEvolutionForUser($user, 90);
        $evolutionMessage = null;
        if (\count($chartEvolution) >= 2) {
            $firstHalf = array_slice($chartEvolution, 0, (int) (count($chartEvolution) / 2));
            $secondHalf = array_slice($chartEvolution, (int) (count($chartEvolution) / 2));
            $moy1 = array_sum(array_column($firstHalf, 'score')) / max(1, count($firstHalf));
            $moy2 = array_sum(array_column($secondHalf, 'score')) / max(1, count($secondHalf));
            $delta = $moy2 - $moy1;
            $pct = $moy1 > 0 ? round(($delta / $moy1) * 100) : 0;
            if ($pct > 0) {
                $evolutionMessage = 'Votre humeur moyenne a augmenté d\'environ ' . abs($pct) . '% sur la période (évolution positive).';
            } elseif ($pct < 0) {
                $evolutionMessage = 'Sur la période, votre humeur moyenne a diminué d\'environ ' . abs($pct) . '%. Pensez à en parler en consultation.';
            }
        }

        return $this->render('dossier_medical/patient_view.html.twig', [
            'dossier' => $dossier,
            'nombre_psychologues' => $nombrePsychologues,
            'derniere_consultation' => $derniereConsultation,
            'consultations_sorted' => $consultationsSorted,
            'age' => $user->getDateNaissance() ? (new \DateTime())->diff($user->getDateNaissance())->y : null,
            'chart_evolution' => $chartEvolution,
            'evolution_message' => $evolutionMessage,
            'notes_prochaine_consultation' => $user->getNotesProchaineConsultation(),
        ]);
    }

    #[Route('/patient/dossier/notes-consultation', name: 'patient_save_notes_consultation', methods: ['POST'])]
    public function patientSaveNotesConsultation(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_PATIENT', $user->getRoles())) {
            return $this->redirectToRoute('app_login');
        }
        $notes = trim((string) $request->request->get('notes_prochaine_consultation', ''));
        $user->setNotesProchaineConsultation($notes === '' ? null : $notes);
        $em->flush();
        $this->addFlash('success', 'Vos points pour la prochaine consultation ont été enregistrés.');
        return $this->redirectToRoute('patient_dossier');
    }

    #[Route('/psychologue/dossiers', name: 'psy_dossiers_list')]
    public function psyListPatients(UserRepository $userRepo, DossierMedicalRepository $dossierRepo, JournalRepository $journalRepo): Response
    {
        if (!$this->isGranted('ROLE_PSYCHOLOGUE')) {
            throw $this->createAccessDeniedException();
        }

        $patients = $userRepo->findByRole('ROLE_PATIENT');
        $dossiers = [];
        foreach ($dossierRepo->findAll() as $d) {
            $dossiers[$d->getPatient()->getId()] = $d;
        }

        // Statistiques basées uniquement sur le journal du patient (humeurs, nombre d'entrées)
        $statsParPatient = [];
        foreach ($patients as $patient) {
            $statsHumeurs = $journalRepo->countByHumeurForUser($patient);
            $nbJournaux = array_sum($statsHumeurs);
            $sosCount = $statsHumeurs['SOS'] ?? 0;
            $enColereCount = $statsHumeurs['en colere'] ?? 0;
            // Cas urgent : au moins un SOS ou plusieurs "en colère" dans le journal
            $urgent = $sosCount > 0 || $enColereCount >= 2;

            $statsParPatient[$patient->getId()] = [
                'nb_journaux' => $nbJournaux,
                'stats_humeurs' => $statsHumeurs,
                'sos_count' => $sosCount,
                'en_colere_count' => $enColereCount,
                'urgent' => $urgent,
            ];
        }

        // Dernière consultation et indicateurs par patient
        $derniereConsultationParPatient = [];
        foreach ($patients as $patient) {
            $dossier = $dossiers[$patient->getId()] ?? null;
            $derniere = null;
            if ($dossier && $dossier->getConsultations()->count() > 0) {
                $convs = $dossier->getConsultations()->toArray();
                usort($convs, fn($c1, $c2) => ($c2->getDate() ?? new \DateTime()) <=> ($c1->getDate() ?? new \DateTime()));
                $derniere = $convs[0];
            }
            $derniereConsultationParPatient[$patient->getId()] = $derniere;
        }

        // Trier : cas urgents en premier
        usort($patients, function ($a, $b) use ($statsParPatient) {
            $urgentA = $statsParPatient[$a->getId()]['urgent'] ?? false;
            $urgentB = $statsParPatient[$b->getId()]['urgent'] ?? false;
            if ($urgentA && !$urgentB) {
                return -1;
            }
            if (!$urgentA && $urgentB) {
                return 1;
            }
            return 0;
        });

        return $this->render('dossier_medical/psy_list.html.twig', [
            'patients' => $patients,
            'dossiers' => $dossiers,
            'stats_par_patient' => $statsParPatient,
            'derniere_consultation_par_patient' => $derniereConsultationParPatient,
        ]);
    }

    #[Route('/psychologue/dossier/{patientId}', name: 'psy_dossier_view')]
    public function psyView(int $patientId, DossierMedicalRepository $dossierRepo, UserRepository $userRepo, JournalRepository $journalRepo, PredictionEvolutionService $predictionService): Response
    {
        if (!$this->isGranted('ROLE_PSYCHOLOGUE')) {
            throw $this->createAccessDeniedException();
        }

        $patient = $userRepo->find($patientId);
        if (!$patient || !in_array('ROLE_PATIENT', $patient->getRoles())) {
            throw $this->createNotFoundException('Patient non trouvé');
        }

        $dossier = $dossierRepo->findByPatient($patientId);

        // Journaux du patient (triés du plus récent au plus ancien)
        $journals = $journalRepo->searchAndSortByUser($patient, null, 'new');

        // Analyses émotionnelles extraites des journaux
        $analyses = [];
        foreach ($journals as $journal) {
            $analysis = $journal->getAnalysisEmotionnelle();
            if ($analysis) {
                $analyses[] = $analysis;
            }
        }

        // Statistiques humeurs
        $statsHumeurs = $journalRepo->countByHumeurForUser($patient);

        // Statistiques analyses (stress, bien-être)
        $statsAnalyses = [
            'moyenne_stress' => 0,
            'moyenne_bien_etre' => 0,
            'emotions_frequentes' => [],
            'derniere_analyse' => null,
        ];
        if (\count($analyses) > 0) {
            $totalStress = 0;
            $totalBienEtre = 0;
            $emotions = [];
            foreach ($analyses as $a) {
                $totalStress += $a->getNiveauStress();
                $totalBienEtre += $a->getScoreBienEtre();
                $e = $a->getEmotionPrincipale();
                $emotions[$e] = ($emotions[$e] ?? 0) + 1;
            }
            $statsAnalyses['moyenne_stress'] = round($totalStress / \count($analyses), 1);
            $statsAnalyses['moyenne_bien_etre'] = round($totalBienEtre / \count($analyses), 1);
            arsort($emotions);
            $statsAnalyses['emotions_frequentes'] = array_slice(array_keys($emotions), 0, 5);
            $statsAnalyses['derniere_analyse'] = $analyses[0];
        }

        // Âge du patient
        $age = $patient->getDateNaissance() ? (new \DateTime())->diff($patient->getDateNaissance())->y : null;

        // Données pour graphique d'évolution des émotions (journal) + dates des consultations
        $chartEvolution = $journalRepo->getEvolutionForUser($patient, 90);
        $consultationDates = [];
        $nbConsultations = 0;
        if ($dossier) {
            $nbConsultations = $dossier->getConsultations()->count();
            foreach ($dossier->getConsultations() as $c) {
                if ($c->getDate()) {
                    $consultationDates[] = $c->getDate()->format('Y-m-d');
                }
            }
        }

        // Prédiction IA d'évolution de l'état du patient
        $prediction = $predictionService->predict($chartEvolution, $statsHumeurs, $statsAnalyses, $nbConsultations);

        return $this->render('dossier_medical/psy_view.html.twig', [
            'dossier' => $dossier,
            'patient' => $patient,
            'journals' => $journals,
            'analyses' => $analyses,
            'stats_humeurs' => $statsHumeurs,
            'stats_analyses' => $statsAnalyses,
            'age' => $age,
            'chart_evolution' => $chartEvolution,
            'consultation_dates' => $consultationDates,
            'prediction' => $prediction,
        ]);
    }

    #[Route('/psychologue/dossier/{patientId}/timeline', name: 'psy_dossier_timeline')]
    public function psyTimeline(int $patientId, DossierMedicalRepository $dossierRepo, UserRepository $userRepo): Response
    {
        if (!$this->isGranted('ROLE_PSYCHOLOGUE')) {
            throw $this->createAccessDeniedException();
        }
        $patient = $userRepo->find($patientId);
        if (!$patient || !in_array('ROLE_PATIENT', $patient->getRoles())) {
            throw $this->createNotFoundException('Patient non trouvé');
        }
        $dossier = $dossierRepo->findByPatient($patientId);
        $consultations = [];
        if ($dossier) {
            $consultations = $dossier->getConsultations()->toArray();
            usort($consultations, fn($a, $b) => ($a->getDate() ?? new \DateTime()) <=> ($b->getDate() ?? new \DateTime()));
        }
        return $this->render('dossier_medical/psy_timeline.html.twig', [
            'patient' => $patient,
            'dossier' => $dossier,
            'consultations' => $consultations,
        ]);
    }

    #[Route('/psychologue/consultation/{id}/prescription', name: 'psy_consultation_prescription', methods: ['POST'])]
    public function addPrescription(int $id, Request $request, EntityManagerInterface $em, DossierMedicalRepository $dossierRepo): Response
    {
        if (!$this->isGranted('ROLE_PSYCHOLOGUE')) {
            throw $this->createAccessDeniedException();
        }
        $consultation = $em->getRepository(Consultation::class)->find($id);
        if (!$consultation) {
            throw $this->createNotFoundException('Consultation non trouvée');
        }
        $contenu = trim((string) $request->request->get('contenu_prescription', ''));
        if ($contenu !== '') {
            $p = new Prescription();
            $p->setConsultation($consultation);
            $p->setContenu($contenu);
            $em->persist($p);
            $em->flush();
            $this->addFlash('success', 'Prescription enregistrée.');
        }
        $patientId = $consultation->getDossier()->getPatient()->getId();
        return $this->redirectToRoute('psy_dossier_view', ['patientId' => $patientId]);
    }

    #[Route('/psychologue/consultation/{id}/document', name: 'psy_consultation_document', methods: ['POST'])]
    public function addDocument(int $id, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isGranted('ROLE_PSYCHOLOGUE')) {
            throw $this->createAccessDeniedException();
        }
        $consultation = $em->getRepository(Consultation::class)->find($id);
        if (!$consultation) {
            throw $this->createNotFoundException('Consultation non trouvée');
        }
        $nom = trim((string) $request->request->get('nom_document', ''));
        $url = trim((string) $request->request->get('url_document', ''));
        if ($nom !== '' && $url !== '') {
            $doc = new ConsultationDocument();
            $doc->setConsultation($consultation);
            $doc->setNom($nom);
            $doc->setTypeFichier('lien');
            $doc->setPathOrUrl($url);
            $em->persist($doc);
            $em->flush();
            $this->addFlash('success', 'Document lié.');
        }
        $patientId = $consultation->getDossier()->getPatient()->getId();
        return $this->redirectToRoute('psy_dossier_view', ['patientId' => $patientId]);
    }

    #[Route('/psychologue/dossier/create/{patientId}', name: 'psy_dossier_create', methods: ['POST'])]
    public function psyCreate(int $patientId, Request $request, EntityManagerInterface $em, UserRepository $userRepo, DossierMedicalRepository $dossierRepo, ValidatorInterface $validator): Response
    {
        if (!$this->isGranted('ROLE_PSYCHOLOGUE')) {
            throw $this->createAccessDeniedException();
        }

        $patient = $userRepo->find($patientId);
        if (!$patient || !in_array('ROLE_PATIENT', $patient->getRoles())) {
            throw $this->createNotFoundException('Patient non trouvé');
        }

        if ($dossierRepo->findByPatient($patientId)) {
            $this->addFlash('error', 'Dossier déjà existant !');
            return $this->redirectToRoute('psy_dossier_view', ['patientId' => $patientId]);
        }

        $dossier = new DossierMedical();
        $dossier->setPatient($patient);
        $dossier->setHistoriqueMedical(trim((string) $request->request->get('historique_medical', '')) ?: 'À compléter');
        $dossier->setNotesPsychologiques(trim((string) $request->request->get('notes_psychologiques', '')) ?: 'À compléter');
        $dossier->setDiagnostic(trim((string) $request->request->get('diagnostic', '')) ?: null);
        $dossier->setTraitementFond(trim((string) $request->request->get('traitement_fond', '')) ?: null);
        $dossier->setObjectifsLongTerme(trim((string) $request->request->get('objectifs_long_terme', '')) ?: null);

        $errors = $validator->validate($dossier);
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
            return $this->redirectToRoute('psy_dossier_view', ['patientId' => $patientId]);
        }

        $em->persist($dossier);
        $em->flush();

        $this->logAudit($em, 'dossier_create', 'DossierMedical', $dossier->getId(), 'Création dossier patient #' . $patientId, $request);

        $this->addFlash('success', 'Dossier créé avec succès !');
        return $this->redirectToRoute('psy_dossier_view', ['patientId' => $patientId]);
    }

    #[Route('/psychologue/dossier/update/{patientId}', name: 'psy_dossier_update', methods: ['POST'])]
    public function psyUpdate(int $patientId, Request $request, EntityManagerInterface $em, DossierMedicalRepository $dossierRepo, ValidatorInterface $validator): Response
    {
        if (!$this->isGranted('ROLE_PSYCHOLOGUE')) {
            throw $this->createAccessDeniedException();
        }

        $dossier = $dossierRepo->findByPatient($patientId);
        if (!$dossier) {
            $this->addFlash('error', 'Dossier non trouvé !');
            return $this->redirectToRoute('psy_dossiers_list');
        }

        $action = $request->request->get('action', 'update_dossier');

        if ($action === 'add_consultation') {
            $dateConsult = $request->request->get('date_consult');
            $compteRendu = trim((string) $request->request->get('compte_rendu', ''));
            if ($dateConsult !== null && $dateConsult !== '' && $compteRendu !== '') {
                $consult = new Consultation();
                $consult->setDate(new \DateTime($dateConsult));
                $consult->setCompteRendu($compteRendu);
                $consult->setHumeurPatient(trim((string) $request->request->get('humeur_patient', '')) ?: null);
                $consult->setSujetAborde(trim((string) $request->request->get('sujet_aborde', '')) ?: null);
                $consult->setObservations(trim((string) $request->request->get('observations', '')) ?: null);
                $consult->setPsychologue($this->getUser());
                $consult->setDossier($dossier);
                $dossier->addConsultation($consult);

                $errors = $validator->validate($consult);
                if (count($errors) > 0) {
                    foreach ($errors as $error) {
                        $this->addFlash('error', $error->getMessage());
                    }
                    return $this->redirectToRoute('psy_dossier_view', ['patientId' => $patientId]);
                }
            } else {
                $this->addFlash('error', 'La date de consultation et le compte rendu sont obligatoires.');
                return $this->redirectToRoute('psy_dossier_view', ['patientId' => $patientId]);
            }
        } else {
            $dossier->setHistoriqueMedical(trim((string) $request->request->get('historique_medical', $dossier->getHistoriqueMedical() ?? '')));
            $dossier->setNotesPsychologiques(trim((string) $request->request->get('notes_psychologiques', $dossier->getNotesPsychologiques() ?? '')));
            $dossier->setDiagnostic(trim((string) $request->request->get('diagnostic', $dossier->getDiagnostic() ?? '')) ?: null);
            $dossier->setTraitementFond(trim((string) $request->request->get('traitement_fond', $dossier->getTraitementFond() ?? '')) ?: null);
            $dossier->setObjectifsLongTerme(trim((string) $request->request->get('objectifs_long_terme', $dossier->getObjectifsLongTerme() ?? '')) ?: null);
            $dossier->setUpdatedAt(new \DateTime());

            $errors = $validator->validate($dossier);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->redirectToRoute('psy_dossier_view', ['patientId' => $patientId]);
            }
        }

        $em->flush();

        if ($action === 'add_consultation') {
            $convs = $dossier->getConsultations()->toArray();
            $lastConsult = end($convs) ?: null;
            $this->logAudit($em, 'consultation_add', 'Consultation', $lastConsult ? $lastConsult->getId() : null, 'Nouvelle consultation dossier #' . $dossier->getId(), $request);
        } else {
            $this->logAudit($em, 'dossier_update', 'DossierMedical', $dossier->getId(), 'Mise à jour dossier patient #' . $patientId, $request);
        }

        $this->addFlash('success', $action === 'add_consultation' ? 'Consultation ajoutée avec succès !' : 'Dossier mis à jour avec succès !');
        return $this->redirectToRoute('psy_dossier_view', ['patientId' => $patientId]);
    }

    private function logAudit(EntityManagerInterface $em, string $action, string $entityType, ?int $entityId, string $details, Request $request): void
    {
        $log = new AuditLog();
        $log->setAction($action);
        $log->setEntityType($entityType);
        $log->setEntityId($entityId);
        $log->setDetails($details);
        $log->setUser($this->getUser());
        $log->setIp($request->getClientIp());
        $em->persist($log);
        $em->flush();
    }
}