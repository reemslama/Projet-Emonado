<?php

namespace App\Controller;

use App\Entity\Consultation;
use App\Entity\DossierMedical;
use App\Repository\DossierMedicalRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class DossierMedicalController extends AbstractController
{
    #[Route('/patient/dossier', name: 'patient_dossier')]
    public function patientView(DossierMedicalRepository $dossierRepo): Response
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_PATIENT', $user->getRoles())) {
            return $this->redirectToRoute('app_login');
        }

        $dossier = $dossierRepo->findByPatient($user->getId());

        return $this->render('dossier_medical/patient_view.html.twig', [
            'dossier' => $dossier,
        ]);
    }

    #[Route('/psychologue/dossiers', name: 'psy_dossiers_list')]
    public function psyListPatients(UserRepository $userRepo): Response
    {
        if (!$this->isGranted('ROLE_PSYCHOLOGUE')) {
            throw $this->createAccessDeniedException();
        }

        $patients = $userRepo->findByRole('ROLE_PATIENT');

        return $this->render('dossier_medical/psy_list.html.twig', [
            'patients' => $patients,
        ]);
    }

    #[Route('/psychologue/dossier/{patientId}', name: 'psy_dossier_view')]
    public function psyView(int $patientId, DossierMedicalRepository $dossierRepo, UserRepository $userRepo): Response
    {
        if (!$this->isGranted('ROLE_PSYCHOLOGUE')) {
            throw $this->createAccessDeniedException();
        }

        $patient = $userRepo->find($patientId);
        if (!$patient || !in_array('ROLE_PATIENT', $patient->getRoles())) {
            throw $this->createNotFoundException('Patient non trouvé');
        }

        $dossier = $dossierRepo->findByPatient($patientId);

        return $this->render('dossier_medical/psy_view.html.twig', [
            'dossier' => $dossier,
            'patient' => $patient,
        ]);
    }

    #[Route('/psychologue/dossier/create/{patientId}', name: 'psy_dossier_create', methods: ['POST'])]
    public function psyCreate(int $patientId, Request $request, EntityManagerInterface $em, UserRepository $userRepo, DossierMedicalRepository $dossierRepo): Response
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
        $dossier->setHistoriqueMedical($request->request->get('historique_medical', ''));
        $dossier->setNotesPsychologiques($request->request->get('notes_psychologiques', ''));

        $em->persist($dossier);
        $em->flush();

        $this->addFlash('success', 'Dossier créé avec succès !');
        return $this->redirectToRoute('psy_dossier_view', ['patientId' => $patientId]);
    }

    #[Route('/psychologue/dossier/update/{patientId}', name: 'psy_dossier_update', methods: ['POST'])]
    public function psyUpdate(int $patientId, Request $request, EntityManagerInterface $em, DossierMedicalRepository $dossierRepo): Response
    {
        if (!$this->isGranted('ROLE_PSYCHOLOGUE')) {
            throw $this->createAccessDeniedException();
        }

        $dossier = $dossierRepo->findByPatient($patientId);
        if (!$dossier) {
            $this->addFlash('error', 'Dossier non trouvé !');
            return $this->redirectToRoute('psy_dossiers_list');
        }

        $dossier->setHistoriqueMedical($request->request->get('historique_medical', $dossier->getHistoriqueMedical()));
        $dossier->setNotesPsychologiques($request->request->get('notes_psychologiques', $dossier->getNotesPsychologiques()));
        $dossier->setUpdatedAt(new \DateTime());

        // Ajout d'une nouvelle consultation si fourni
        $dateConsult = $request->request->get('date_consult');
        $compteRendu = $request->request->get('compte_rendu');
        if ($dateConsult && $compteRendu) {
            $consult = new Consultation();
            $consult->setDate(new \DateTime($dateConsult));
            $consult->setCompteRendu($compteRendu);
            $consult->setPsychologue($this->getUser());
            $dossier->addConsultation($consult);
        }

        $em->flush();

        $this->addFlash('success', 'Dossier mis à jour avec succès !');
        return $this->redirectToRoute('psy_dossier_view', ['patientId' => $patientId]);
    }
}