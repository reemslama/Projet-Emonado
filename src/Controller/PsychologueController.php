<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PsychologueController extends AbstractController
{
    #[Route('/psychologue', name: 'psychologue_index')]
    public function index(): Response
    {
        return $this->render('psychologue/index.html.twig');
    }

    #[Route('/psychologue/profil', name: 'psychologue_profil')]
    public function profil(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();
<<<<<<< HEAD
        if (!$user) {
            return $this->redirectToRoute('app_login');
=======
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
>>>>>>> d9465e5 (finalVersionByTeam)
        }

        if ($request->isMethod('POST')) {
            $user->setNom((string) $request->request->get('nom'));
            $user->setPrenom((string) $request->request->get('prenom'));
            $tel = $request->request->get('telephone');
            $user->setTelephone(is_string($tel) && $tel !== '' ? $tel : null);
            $spec = $request->request->get('specialite');
            $user->setSpecialite(is_string($spec) && $spec !== '' ? $spec : null);

            $password = $request->request->get('password');
            if (is_string($password) && $password !== '') {
                $user->setPassword($passwordHasher->hashPassword($user, $password));
            }

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Profil mis à jour avec succès !');
            return $this->redirectToRoute('psychologue_profil');
        }

        return $this->render('profil_psychologue/index.html.twig', [
            'user' => $user,
            'error' => null, // <-- corrige l'erreur
        ]);
    }

    #[Route('/psychologue/profil/delete', name: 'psychologue_profil_delete', methods: ['POST'])]
    public function delete(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if ($user) {
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'Votre profil a été supprimé avec succès !');
        }
        return $this->redirectToRoute('app_login');
    }
<<<<<<< HEAD
=======

    #[Route('/psychologue/journaux', name: 'psychologue_journals', methods: ['GET'])]
    public function journals(Request $request, JournalRepository $journalRepository): Response
    {
        $this->assertPsyArea();

        $keyword = (string) $request->query->get('q', '');
        $sort = (string) $request->query->get('sort', 'recent');
        $journals = $journalRepository->searchAndSortAll($keyword, $sort);

        return $this->render('psychologue/journals.html.twig', [
            'journals' => $journals,
            'keyword' => $keyword,
            'sort' => $sort,
        ]);
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

        usort($insights, static fn(array $a, array $b): int => $b['pack']['metrics']['sampleSize'] <=> $a['pack']['metrics']['sampleSize']);

        return $this->render('psychologue/insights.html.twig', ['insights' => $insights]);
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
            $minutes = $createdAt ? max(1, (int)floor(($now->getTimestamp() - $createdAt->getTimestamp()) / 60)) : 0;
            $priority = 'normal';
            if ($minutes >= 180 || $case->getHumeur() === 'SOS') $priority = 'haute';
            elseif ($minutes >= 60) $priority = 'moyenne';

            $rows[] = ['case' => $case, 'minutes' => $minutes, 'priority' => $priority];
        }

        return $this->render('psychologue/voice_cases.html.twig', ['rows' => $rows]);
    }

    #[Route('/psychologue/statistique/rdv', name: 'psychologue_stats_rdv')]
    public function statsRdv(RendezVousRepository $rdvRepo): Response
    {
        $stats = $rdvRepo->getStatsParMois();
        return $this->render('rendez_vous/stats_mois.html.twig', ['stats' => $stats]);
    }
>>>>>>> d9465e5 (finalVersionByTeam)
}
