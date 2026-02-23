<?php

namespace App\Controller;

use App\Entity\Consultation;
use App\Entity\DossierMedical;
use App\Repository\AnalyseEmotionnelleRepository;
use App\Repository\DossierMedicalRepository;
use App\Service\TherapeuticCompanionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
        Request $request,
        ValidatorInterface $validator
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_PATIENT');

        $patient = $this->getUser();
        if (!$patient) {
            return $this->redirectToRoute('app_login');
        }

        $dossier = $dossierRepo->findOneBy(['patient' => $patient]);

        if (!$dossier) {
            $dossier = new DossierMedical();
            $dossier->setPatient($patient);
            $em->persist($dossier);
            $em->flush();
            $em->refresh($dossier);
        }

        if ($request->isMethod('POST') && $request->request->get('action') === 'add_consultation') {
            $dateConsult = $request->request->get('date_consult');
            $compteRendu = trim((string) $request->request->get('compte_rendu', ''));

            if ($dateConsult === null || $dateConsult === '' || $compteRendu === '') {
                $this->addFlash('error', 'La date de consultation et le compte rendu sont obligatoires.');
                $consultations = $dossier->getConsultations()->toArray();
                usort($consultations, fn ($a, $b) => ($b->getDate() <=> $a->getDate()) ?: 0);

                return $this->render('patient/consultations.html.twig', [
                    'dossier' => $dossier,
                    'consultations' => $consultations,
                ]);
            }

            $consultation = new Consultation();
            $consultation->setDossier($dossier);
            $consultation->setDate(new \DateTime($dateConsult));
            $consultation->setCompteRendu($compteRendu);
            $consultation->setPsychologue(null);

            $errors = $validator->validate($consultation);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                $consultations = $dossier->getConsultations()->toArray();
                usort($consultations, fn ($a, $b) => ($b->getDate() <=> $a->getDate()) ?: 0);

                return $this->render('patient/consultations.html.twig', [
                    'dossier' => $dossier,
                    'consultations' => $consultations,
                ]);
            }

            $em->persist($consultation);
            $em->flush();

            $this->addFlash('success', 'Votre consultation a ete ajoutee avec succes !');

            return $this->redirectToRoute('patient_consultations');
        }

        $consultations = $dossier->getConsultations()->toArray();
        usort($consultations, function ($a, $b) {
            $dateA = $a->getDate();
            $dateB = $b->getDate();
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

    #[Route('/patient/conseils-ia', name: 'patient_ai_conseils', methods: ['GET'])]
    public function aiConseils(
        AnalyseEmotionnelleRepository $analyseEmotionnelleRepository,
        TherapeuticCompanionService $therapeuticCompanionService
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $patient = $this->getUser();
        if (!$patient) {
            return $this->redirectToRoute('app_login');
        }

        $analyses = $analyseEmotionnelleRepository->findRecentForUser($patient, 7);
        $pack = $therapeuticCompanionService->buildPack($analyses);

        return $this->render('patient/ia_conseils.html.twig', [
            'pack' => $pack,
        ]);
    }
}
