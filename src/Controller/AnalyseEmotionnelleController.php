<?php

namespace App\Controller;

use App\Entity\AnalyseEmotionnelle;
use App\Entity\Journal;
use App\Form\AnalyseEmotionnelleType;
use App\Repository\AnalyseEmotionnelleRepository;
use App\Service\CoachSuggestionService;
use App\Service\EmotionAnalysisService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/analyse/emotionnelle')]
final class AnalyseEmotionnelleController extends AbstractController
{
    #[Route(name: 'app_analyse_emotionnelle_index', methods: ['GET'])]
    public function index(AnalyseEmotionnelleRepository $analyseEmotionnelleRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        $canViewAll = $this->isGranted('ROLE_ADMIN')
            || $this->isGranted('ROLE_PSYCHOLOGUE')
            || $this->isGranted('ROLE_PSY');

        return $this->render('analyse_emotionnelle/index.html.twig', [
            'analyse_emotionnelles' => $analyseEmotionnelleRepository->findForUserContext($user, $canViewAll),
        ]);
    }

    #[Route('/new', name: 'app_analyse_emotionnelle_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $analyseEmotionnelle = new AnalyseEmotionnelle();
        $form = $this->createForm(AnalyseEmotionnelleType::class, $analyseEmotionnelle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($analyseEmotionnelle);
            $entityManager->flush();

            return $this->redirectToRoute('app_analyse_emotionnelle_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('analyse_emotionnelle/new.html.twig', [
            'analyse_emotionnelle' => $analyseEmotionnelle,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_analyse_emotionnelle_show', methods: ['GET'])]
    public function show(
        AnalyseEmotionnelle $analyseEmotionnelle,
        CoachSuggestionService $coachSuggestionService
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        if (
            $analyseEmotionnelle->getJournal()?->getUser() !== $user
            && !$this->isGranted('ROLE_ADMIN')
            && !$this->isGranted('ROLE_PSYCHOLOGUE')
            && !$this->isGranted('ROLE_PSY')
        ) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('analyse_emotionnelle/show.html.twig', [
            'analyse_emotionnelle' => $analyseEmotionnelle,
            'coach' => $coachSuggestionService->buildFromAnalyse($analyseEmotionnelle),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_analyse_emotionnelle_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, AnalyseEmotionnelle $analyseEmotionnelle, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        if (
            $analyseEmotionnelle->getJournal()?->getUser() !== $user
            && !$this->isGranted('ROLE_ADMIN')
            && !$this->isGranted('ROLE_PSYCHOLOGUE')
            && !$this->isGranted('ROLE_PSY')
        ) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(AnalyseEmotionnelleType::class, $analyseEmotionnelle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_analyse_emotionnelle_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('analyse_emotionnelle/edit.html.twig', [
            'analyse_emotionnelle' => $analyseEmotionnelle,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_analyse_emotionnelle_delete', methods: ['POST'])]
    public function delete(Request $request, AnalyseEmotionnelle $analyseEmotionnelle, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        if (
            $analyseEmotionnelle->getJournal()?->getUser() !== $user
            && !$this->isGranted('ROLE_ADMIN')
            && !$this->isGranted('ROLE_PSYCHOLOGUE')
            && !$this->isGranted('ROLE_PSY')
        ) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$analyseEmotionnelle->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($analyseEmotionnelle);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_analyse_emotionnelle_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/from-journal/{id}', name: 'app_analyse_emotionnelle_from_journal', methods: ['POST'])]
    public function fromJournal(
        Journal $journal,
        EmotionAnalysisService $emotionAnalysisService,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $isPsyArea = $this->isGranted('ROLE_ADMIN')
            || $this->isGranted('ROLE_PSYCHOLOGUE')
            || $this->isGranted('ROLE_PSY');
        $backRoute = $isPsyArea ? 'psychologue_journals' : 'app_journal_index';

        $user = $this->getUser();

        // Autoriser le proprietaire du journal, les admins et psychologues
        if (
            $journal->getUser() !== $user
            && !$this->isGranted('ROLE_ADMIN')
            && !$this->isGranted('ROLE_PSYCHOLOGUE')
            && !$this->isGranted('ROLE_PSY')
        ) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid(
            'analyze' . $journal->getId(),
            (string) $request->request->get('_token')
        )) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute($backRoute);
        }

        // Reutiliser l'analyse si elle existe deja, sinon en creer une nouvelle
        $analyse = $journal->getAnalysisEmotionnelle() ?? new AnalyseEmotionnelle();

        $result = $emotionAnalysisService->analyze((string) $journal->getContenu());

        $analyse
            ->setJournal($journal)
            ->setEmotionPrincipale($result['emotionPrincipale'] ?? 'neutre')
            ->setNiveauStress($result['niveauStress'] ?? 0)
            ->setScoreBienEtre($result['scoreBienEtre'] ?? 50)
            ->setResumeIA($result['resumeIA'] ?? 'Analyse non disponible.')
            ->setDateAnalyse(new \DateTime());

        try {
            $entityManager->persist($analyse);
            $entityManager->flush();

            $this->addFlash('success', 'Analyse emotionnelle generee avec succes.');

            return $this->redirectToRoute('app_analyse_emotionnelle_show', [
                'id' => $analyse->getId(),
            ]);
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Impossible d\'enregistrer l\'analyse emotionnelle pour le moment.');

            return $this->redirectToRoute($backRoute);
        }
    }
}
