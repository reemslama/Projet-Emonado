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
use Symfony\Component\Validator\Validator\ValidatorInterface;

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

        return $this->render('dossier_medical/patient_view.html.twig', [
            'dossier' => $dossier,
            'nombre_psychologues' => $nombrePsychologues,
            'derniere_consultation' => $derniereConsultation,
            'consultations_sorted' => $consultationsSorted,
            'age' => $user->getDateNaissance() ? (new \DateTime())->diff($user->getDateNaissance())->y : null,
        ]);
    }

    #[Route('/psychologue/dossiers', name: 'psy_dossiers_list')]
    public function psyListPatients(UserRepository $userRepo, DossierMedicalRepository $dossierRepo): Response
    {
        if (!$this->isGranted('ROLE_PSYCHOLOGUE')) {
            throw $this->createAccessDeniedException();
        }

        $patients = $userRepo->findByRole('ROLE_PATIENT');
        $dossiers = [];
        foreach ($dossierRepo->findAll() as $d) {
            $dossiers[$d->getPatient()->getId()] = $d;
        }

        return $this->render('dossier_medical/psy_list.html.twig', [
            'patients' => $patients,
            'dossiers' => $dossiers,
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
        $dossier->setHistoriqueMedical(trim((string) $request->request->get('historique_medical', '')));
        $dossier->setNotesPsychologiques(trim((string) $request->request->get('notes_psychologiques', '')));

        $errors = $validator->validate($dossier);
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
            return $this->redirectToRoute('psy_dossier_view', ['patientId' => $patientId]);
        }

        $em->persist($dossier);
        $em->flush();

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

        $this->addFlash('success', $action === 'add_consultation' ? 'Consultation ajoutée avec succès !' : 'Dossier mis à jour avec succès !');
        return $this->redirectToRoute('psy_dossier_view', ['patientId' => $patientId]);
    }
}