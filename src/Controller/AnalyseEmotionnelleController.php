<?php

namespace App\Controller;

use App\Entity\AnalyseEmotionnelle;
use App\Entity\Journal;
use App\Form\AnalyseEmotionnelleType;
use App\Repository\AnalyseEmotionnelleRepository;
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
        return $this->render('analyse_emotionnelle/index.html.twig', [
            'analyse_emotionnelles' => $analyseEmotionnelleRepository->findAll(),
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
    public function show(AnalyseEmotionnelle $analyseEmotionnelle): Response
    {
        return $this->render('analyse_emotionnelle/show.html.twig', [
            'analyse_emotionnelle' => $analyseEmotionnelle,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_analyse_emotionnelle_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, AnalyseEmotionnelle $analyseEmotionnelle, EntityManagerInterface $entityManager): Response
    {
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

        $user = $this->getUser();

        // Autoriser le propriétaire du journal, les admins et psychologues
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

            return $this->redirectToRoute('app_journal_index');
        }

        // Réutiliser l'analyse si elle existe déjà, sinon en créer une nouvelle
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

            $this->addFlash('success', 'Analyse émotionnelle générée avec succès.');

            return $this->redirectToRoute('app_analyse_emotionnelle_show', [
                'id' => $analyse->getId(),
            ]);
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Impossible d\'enregistrer l\'analyse émotionnelle pour le moment.');

            return $this->redirectToRoute('app_journal_index');
        }
    }
}
