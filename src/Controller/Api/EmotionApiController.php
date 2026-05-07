<?php

namespace App\Controller\Api;

use App\Repository\AnalyseEmotionnelleRepository;
use App\Repository\UserRepository;
use App\Service\TherapeuticCompanionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class EmotionApiController extends AbstractController
{
    #[Route('/journaux/{id}/analyse', name: 'api_journal_analyse_show', methods: ['GET'])]
    public function journalAnalyse(int $id, AnalyseEmotionnelleRepository $analyseEmotionnelleRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $analyse = $analyseEmotionnelleRepository->findOneByJournalId($id);
        if (!$analyse) {
            return $this->json(['message' => 'Analyse introuvable pour ce journal.'], Response::HTTP_NOT_FOUND);
        }

        $journalOwner = $analyse->getJournal()?->getUser();
        $user = $this->getUser();
        $canRead = $journalOwner === $user
            || $this->isGranted('ROLE_ADMIN')
            || $this->isGranted('ROLE_PSYCHOLOGUE')
            || $this->isGranted('ROLE_PSY');

        if (!$canRead) {
            return $this->json(['message' => 'Acces refuse.'], Response::HTTP_FORBIDDEN);
        }

        return $this->json([
            'journalId' => $id,
            'analyse' => [
                'id' => $analyse->getId(),
                'emotionPrincipale' => $analyse->getEmotionPrincipale(),
                'niveauStress' => $analyse->getNiveauStress(),
                'scoreBienEtre' => $analyse->getScoreBienEtre(),
                'resumeIA' => $analyse->getResumeIA(),
                'dateAnalyse' => $analyse->getDateAnalyse()?->format(\DateTimeInterface::ATOM),
            ],
        ]);
    }

    #[Route('/patients/{id}/studio-therapeutique', name: 'api_patient_therapeutic_pack', methods: ['GET'])]
    public function patientTherapeuticPack(
        int $id,
        UserRepository $userRepository,
        AnalyseEmotionnelleRepository $analyseEmotionnelleRepository,
        TherapeuticCompanionService $therapeuticCompanionService
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $patient = $userRepository->find($id);
        if (!$patient) {
            return $this->json(['message' => 'Patient introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $currentUser = $this->getUser();
        $canRead = $currentUser === $patient
            || $this->isGranted('ROLE_ADMIN')
            || $this->isGranted('ROLE_PSYCHOLOGUE')
            || $this->isGranted('ROLE_PSY');

        if (!$canRead) {
            return $this->json(['message' => 'Acces refuse.'], Response::HTTP_FORBIDDEN);
        }

        $analyses = $analyseEmotionnelleRepository->findRecentForUser($patient, 7);
        $pack = $therapeuticCompanionService->buildPack($analyses);

        return $this->json([
            'patient' => [
                'id' => $patient->getId(),
                'nom' => $patient->getNom(),
                'prenom' => $patient->getPrenom(),
            ],
            'therapeuticPack' => $pack,
        ]);
    }
}
