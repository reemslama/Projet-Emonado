<?php

namespace App\Controller;

use App\Entity\DossierMedical;
use App\Entity\Consultation;
use App\Entity\User;
use App\Repository\DossierMedicalRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DossierMedicalController extends AbstractController
{
    #[Route('/psychologue/dossiers', name: 'dossier_medical_psy_list')]
    public function psyList(DossierMedicalRepository $dossierRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PSYCHOLOGUE');

        $dossiers = $dossierRepo->findAll();

        return $this->render('dossier_medical/psy_list.html.twig', [
            'dossiers' => $dossiers,
        ]);
    }

    #[Route('/psychologue/dossier/{id}', name: 'dossier_medical_psy_view')]
    public function psyView(
        DossierMedical $dossier,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_PSYCHOLOGUE');

        $psychologue = $this->getUser();

        // Ajouter une consultation
        if ($request->isMethod('POST') && $request->request->get('action') === 'add_consultation') {
            $consultation = new Consultation();
            $consultation->setPsychologue($psychologue);
            $consultation->setDossier($dossier);
            $consultation->setNotes($request->request->get('notes'));
            
            $dateConsultation = $request->request->get('date_consultation');
            if ($dateConsultation) {
                $consultation->setDateConsultation(new \DateTime($dateConsultation));
            }

            $em->persist($consultation);
            $em->flush();

            $this->addFlash('success', 'Consultation ajoutée avec succès !');
            return $this->redirectToRoute('dossier_medical_psy_view', ['id' => $dossier->getId()]);
        }

        // Mettre à jour les notes psychologiques
        if ($request->isMethod('POST') && $request->request->get('action') === 'update_notes') {
            $dossier->setNotesPsychologiques($request->request->get('notes_psychologiques'));
            $dossier->setUpdatedAt(new \DateTime());
            $em->flush();

            $this->addFlash('success', 'Notes mises à jour avec succès !');
            return $this->redirectToRoute('dossier_medical_psy_view', ['id' => $dossier->getId()]);
        }

        return $this->render('dossier_medical/psy_view.html.twig', [
            'dossier' => $dossier,
        ]);
    }

    #[Route('/patient/dossier', name: 'dossier_medical_patient_view')]
    public function patientView(
        DossierMedicalRepository $dossierRepo, 
        EntityManagerInterface $em,
        Request $request
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_PATIENT');

        $patient = $this->getUser();
        if (!$patient) {
            return $this->redirectToRoute('app_login');
        }
        
        $dossier = $dossierRepo->findOneBy(['patient' => $patient]);

        // Créer un dossier s'il n'existe pas
        if (!$dossier) {
            $dossier = new DossierMedical();
            $dossier->setPatient($patient);
            $em->persist($dossier);
            $em->flush();
            // Recharger le dossier pour s'assurer que toutes les relations sont chargées
            $em->refresh($dossier);
        }

        // Mettre à jour l'historique médical
        if ($request->isMethod('POST') && $request->request->get('action') === 'update_historique') {
            $dossier->setHistoriqueMedical($request->request->get('historique_medical'));
            $dossier->setUpdatedAt(new \DateTime());
            $em->flush();

            $this->addFlash('success', 'Votre historique médical a été mis à jour avec succès !');
            return $this->redirectToRoute('dossier_medical_patient_view');
        }

        // Ajouter une consultation (par le patient)
        if ($request->isMethod('POST') && $request->request->get('action') === 'add_consultation_patient') {
            $consultation = new Consultation();
            $consultation->setDossier($dossier);
            $consultation->setNotes($request->request->get('notes'));
            
            $dateConsultation = $request->request->get('date_consultation');
            if ($dateConsultation) {
                $consultation->setDateConsultation(new \DateTime($dateConsultation));
            } else {
                $consultation->setDateConsultation(new \DateTime());
            }

            // Le psychologue est optionnel pour les consultations créées par le patient
            // Il pourra être assigné plus tard par un psychologue
            $consultation->setPsychologue(null);

            $em->persist($consultation);
            $em->flush();

            $this->addFlash('success', 'Votre consultation a été ajoutée avec succès !');
            return $this->redirectToRoute('dossier_medical_patient_view');
        }

        // Tri des consultations par date (plus récentes en premier)
        $consultations = $dossier->getConsultations()->toArray();
        usort($consultations, function ($a, $b) {
            $dateA = $a->getDateConsultation();
            $dateB = $b->getDateConsultation();
            if (!$dateA && !$dateB) return 0;
            if (!$dateA) return 1;
            if (!$dateB) return -1;
            return $dateB <=> $dateA;
        });

        // Dernière consultation
        $derniereConsultation = !empty($consultations) ? $consultations[0] : null;

        // Nombre de psychologues distincts
        $psychologueIds = [];
        foreach ($dossier->getConsultations() as $c) {
            $psychologue = $c->getPsychologue();
            if ($psychologue) {
                $id = $psychologue->getId();
                if (!in_array($id, $psychologueIds, true)) {
                    $psychologueIds[] = $id;
                }
            }
        }

        // Âge du patient (si date de naissance)
        $age = null;
        if ($patient->getDateNaissance()) {
            $now = new \DateTime();
            $age = $now->diff($patient->getDateNaissance())->y;
        }

        return $this->render('dossier_medical/patient_view.html.twig', [
            'dossier' => $dossier,
            'consultations_sorted' => $consultations,
            'derniere_consultation' => $derniereConsultation,
            'nombre_psychologues' => count($psychologueIds),
            'age' => $age,
        ]);
    }

    #[Route('/psychologue/dossier/create/{patientId}', name: 'dossier_medical_create')]
    public function createDossier(
        int $patientId,
        UserRepository $userRepo,
        DossierMedicalRepository $dossierRepo,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_PSYCHOLOGUE');

        $patient = $userRepo->find($patientId);
        if (!$patient) {
            $this->addFlash('error', 'Patient non trouvé.');
            return $this->redirectToRoute('dossier_medical_psy_list');
        }

        // Vérifier si un dossier existe déjà
        $existingDossier = $dossierRepo->findOneBy(['patient' => $patient]);
        if ($existingDossier) {
            return $this->redirectToRoute('dossier_medical_psy_view', ['id' => $existingDossier->getId()]);
        }

        $dossier = new DossierMedical();
        $dossier->setPatient($patient);
        $em->persist($dossier);
        $em->flush();

        $this->addFlash('success', 'Dossier médical créé avec succès !');
        return $this->redirectToRoute('dossier_medical_psy_view', ['id' => $dossier->getId()]);
    }
}
