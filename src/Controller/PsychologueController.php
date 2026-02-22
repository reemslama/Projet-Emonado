<?php

namespace App\Controller;

use App\Repository\AnalyseEmotionnelleRepository;
use App\Repository\JournalRepository;
use App\Repository\RendezVousRepository;
use App\Service\TherapeuticCompanionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class PsychologueController extends AbstractController
{
    private function assertPsyArea(): void
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        if (
            !$this->isGranted('ROLE_ADMIN')
            && !$this->isGranted('ROLE_PSYCHOLOGUE')
            && !$this->isGranted('ROLE_PSY')
        ) {
            throw $this->createAccessDeniedException();
        }
    }

    #[Route('/psychologue', name: 'psychologue_index')]
    public function index(JournalRepository $journalRepository): Response
    {
        return $this->render('psychologue/index.html.twig', [
            'pendingVoiceCount' => $journalRepository->countPendingVoiceCases(),
        ]);
    }

    #[Route('/psychologue/journaux', name: 'psychologue_journals', methods: ['GET'])]
    public function journals(Request $request, JournalRepository $journalRepository): Response
    {
        $this->assertPsyArea();

        $keyword = $request->query->get('q', '');
        $sort = $request->query->get('sort', 'recent');
        $journals = $journalRepository->searchAndSortAll($keyword, $sort);

        return $this->render('psychologue/journals.html.twig', [
            'journals' => $journals,
            'keyword' => $keyword,
            'sort' => $sort,
        ]);
    }

    #[Route('/psychologue/profil', name: 'psychologue_profil')]
    public function profil(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $user->setNom($request->request->get('nom'));
            $user->setPrenom($request->request->get('prenom'));
            $user->setTelephone($request->request->get('telephone'));
            $user->setSpecialite($request->request->get('specialite'));

            $password = $request->request->get('password');
            if ($password) {
                $user->setPassword($passwordHasher->hashPassword($user, $password));
            }

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Profil mis a jour avec succes !');
            return $this->redirectToRoute('psychologue_profil');
        }

        return $this->render('profil_psychologue/index.html.twig', [
            'user' => $user,
            'error' => null,
        ]);
    }

    #[Route('/psychologue/profil/delete', name: 'psychologue_profil_delete', methods: ['POST'])]
    public function delete(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if ($user) {
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'Votre profil a ete supprime avec succes !');
        }

        return $this->redirectToRoute('app_login');
    }

    #[Route('/psychologue/parcours-emotionnel', name: 'psychologue_emotion_insights', methods: ['GET'])]
    public function emotionInsights(
        AnalyseEmotionnelleRepository $analyseEmotionnelleRepository,
        TherapeuticCompanionService $therapeuticCompanionService
    ): Response {
        $this->assertPsyArea();

        $grouped = $analyseEmotionnelleRepository->findRecentGroupedByUser(7);
        $insights = [];

        foreach ($grouped as $item) {
            $insights[] = [
                'user' => $item['user'],
                'pack' => $therapeuticCompanionService->buildPack($item['analyses']),
            ];
        }

        usort(
            $insights,
            static fn (array $a, array $b): int => $b['pack']['metrics']['sampleSize'] <=> $a['pack']['metrics']['sampleSize']
        );

        return $this->render('psychologue/insights.html.twig', [
            'insights' => $insights,
        ]);
    }

    #[Route('/psychologue/vocaux', name: 'psychologue_voice_cases', methods: ['GET'])]
    public function voiceCases(JournalRepository $journalRepository): Response
    {
        $this->assertPsyArea();

        $cases = $journalRepository->findPendingVoiceCases();
        $rows = [];
        $now = new \DateTimeImmutable();
        foreach ($cases as $case) {
            $createdAt = $case->getDateCreation();
            $minutes = $createdAt ? max(1, (int) floor(($now->getTimestamp() - $createdAt->getTimestamp()) / 60)) : 0;
            $priority = 'normal';
            if ($minutes >= 180 || $case->getHumeur() === 'SOS') {
                $priority = 'haute';
            } elseif ($minutes >= 60) {
                $priority = 'moyenne';
            }

            $rows[] = [
                'case' => $case,
                'minutes' => $minutes,
                'priority' => $priority,
            ];
        }

        return $this->render('psychologue/voice_cases.html.twig', [
            'rows' => $rows,
        ]);
    }

    #[Route('/psychologue/vocaux/{id}/conseiller', name: 'psychologue_voice_case_advise', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function adviseVoiceCase(int $id, Request $request, JournalRepository $journalRepository, EntityManagerInterface $em): Response
    {
        $this->assertPsyArea();

        $case = $journalRepository->find($id);
        if (!$case || $case->getInputMode() !== 'voice') {
            $this->addFlash('error', 'Cas vocal introuvable.');
            return $this->redirectToRoute('psychologue_voice_cases');
        }

        if (!$this->isCsrfTokenValid('advise_voice_case_' . $case->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('psychologue_voice_cases');
        }

        $advice = trim((string) $request->request->get('patient_advice'));
        if (mb_strlen($advice) < 15) {
            $this->addFlash('error', 'Conseil trop court (15 caracteres minimum).');
            return $this->redirectToRoute('psychologue_voice_cases');
        }

        $case->setPsychologueCaseDescription($advice);
        $case->setPsychologueReviewedAt(new \DateTime());
        $case->setPatientAdviceSeenAt(null);
        $em->flush();

        $this->addFlash('success', 'Conseil envoye au patient avec succes.');
        return $this->redirectToRoute('psychologue_voice_cases');
    }

    #[Route('/psychologue/statistique/rdv', name: 'psychologue_stats_rdv')]
    public function statsRdv(RendezVousRepository $rdvRepo): Response
    {
        $stats = $rdvRepo->getStatsParMois();

        return $this->render('rendez_vous/stats_mois.html.twig', [
            'stats' => $stats,
        ]);
    }
}
