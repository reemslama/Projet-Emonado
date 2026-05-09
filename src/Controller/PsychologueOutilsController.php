<?php

namespace App\Controller;

use App\Entity\Journal;
use App\Repository\JournalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class PsychologueOutilsController extends AbstractController
{
    private function assertPsy(): void
    {
        if (!$this->isGranted('ROLE_PSYCHOLOGUE') && !$this->isGranted('ROLE_PSY') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }
    }

    #[Route('/psychologue/journaux', name: 'psychologue_journals', methods: ['GET'])]
    public function journals(Request $request, JournalRepository $journalRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $this->assertPsy();

        $keyword = (string) $request->query->get('q', '');
        $sort = (string) $request->query->get('sort', 'recent');

        $journals = $journalRepository->searchAndSortAll($keyword, $sort);

        return $this->render('psychologue/journals.html.twig', [
            'journals' => $journals,
            'keyword' => $keyword,
            'sort' => $sort,
        ]);
    }

    #[Route('/psychologue/vocaux', name: 'psychologue_voice_cases', methods: ['GET'])]
    public function voiceCases(JournalRepository $journalRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $this->assertPsy();

        $cases = $journalRepository->findPendingVoiceCases();
        $now = new \DateTimeImmutable();

        $rows = array_map(static function (Journal $j) use ($now): array {
            $created = $j->getDateCreation() ?? $now;
            $minutes = max(0, (int) floor(($now->getTimestamp() - $created->getTimestamp()) / 60));

            // Priorité simple: plus ancien => plus prioritaire
            $priority = 'basse';
            if ($minutes >= 180) {
                $priority = 'haute';
            } elseif ($minutes >= 60) {
                $priority = 'moyenne';
            }

            return [
                'case' => $j,
                'minutes' => $minutes,
                'priority' => $priority,
            ];
        }, $cases);

        return $this->render('psychologue/voice_cases.html.twig', [
            'rows' => $rows,
        ]);
    }

    #[Route('/psychologue/vocaux/{id}/advise', name: 'psychologue_voice_case_advise', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function adviseVoiceCase(
        Request $request,
        int $id,
        JournalRepository $journalRepository,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $this->assertPsy();

        $journal = $journalRepository->find($id);
        if (!$journal) {
            $this->addFlash('error', 'Journal introuvable.');
            return $this->redirectToRoute('psychologue_voice_cases');
        }

        if (!$this->isCsrfTokenValid('advise_voice_case_' . $journal->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('psychologue_voice_cases');
        }

        $advice = trim((string) $request->request->get('patient_advice', ''));
        if ($advice === '' || mb_strlen($advice) < 15) {
            $this->addFlash('error', 'Le conseil doit contenir au moins 15 caractères.');
            return $this->redirectToRoute('psychologue_voice_cases');
        }

        // On stocke le conseil dans un champ existant (sans toucher au schéma DB)
        $journal->setPsychologueCaseDescription($advice);
        $journal->markPsychologueReviewedAt(new \DateTimeImmutable());
        $em->flush();

        $this->addFlash('success', 'Conseil envoyé au patient.');
        return $this->redirectToRoute('psychologue_voice_cases');
    }
}

