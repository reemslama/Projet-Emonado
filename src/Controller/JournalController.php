<?php

namespace App\Controller;

use App\Entity\Journal;
use App\Form\JournalType;
use App\Repository\JournalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/journal')]
class JournalController extends AbstractController
{
    #[Route('/', name: 'app_journal_index', methods: ['GET'])]
    public function index(
        Request $request,
        JournalRepository $journalRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();

        $keyword = $request->query->get('q', '');
        $sort    = $request->query->get('sort', 'recent');

        $journals = $journalRepository->searchAndSortByUser(
            $user,
            $keyword,
            $sort
        );

        $stats = $journalRepository->countByHumeurForUser($user);

        return $this->render('journal/index.html.twig', [
            'journals' => $journals,
            'stats'    => $stats,
            'keyword'  => $keyword,
            'sort'     => $sort,
        ]);
    }

    #[Route('/new', name: 'app_journal_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $journal = new Journal();
        $form = $this->createForm(JournalType::class, $journal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $journal->setUser($this->getUser());
            $journal->setDateCreation(new \DateTime());

            $entityManager->persist($journal);
            $entityManager->flush();

            return $this->redirectToRoute('app_journal_index');
        }

        return $this->render('journal/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_journal_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Journal $journal,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if ($journal->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(JournalType::class, $journal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_journal_index');
        }

        return $this->render('journal/edit.html.twig', [
            'form'    => $form,
            'journal' => $journal,
        ]);
    }

    #[Route('/{id}', name: 'app_journal_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Journal $journal,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if ($journal->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid(
            'delete' . $journal->getId(),
            $request->request->get('_token')
        )) {
            $entityManager->remove($journal);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_journal_index');
    }
}
