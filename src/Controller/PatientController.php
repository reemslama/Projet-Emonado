<?php

namespace App\Controller;

use App\Entity\Consultation;
use App\Entity\DossierMedical;
use App\Repository\DossierMedicalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PatientController extends AbstractController
{
    #[Route('/patient', name: 'patient_index')]
    public function index(): Response
    {
        return $this->render('patient/index.html.twig');
    }

    #[Route('/patient/consultations', name: 'patient_consultations')]
    public function consultations(
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
            $em->refresh($dossier);
        }

        // Ajouter une consultation
        if ($request->isMethod('POST') && $request->request->get('action') === 'add_consultation') {
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
            $consultation->setPsychologue(null);

            $em->persist($consultation);
            $em->flush();

            $this->addFlash('success', 'Votre consultation a été ajoutée avec succès !');
            return $this->redirectToRoute('patient_consultations');
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

        return $this->render('patient/consultations.html.twig', [
            'dossier' => $dossier,
            'consultations' => $consultations,
        ]);
    }
}
