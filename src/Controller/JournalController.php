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
final class JournalController extends AbstractController
{
   #[Route('/', name: 'app_journal_index', methods: ['GET'])]
public function index(Request $request, JournalRepository $journalRepository): Response
{
    $keyword = $request->query->get('q');
    $sort = $request->query->get('sort');

    $journals = $journalRepository->searchAndSort($keyword, $sort);
    $stats = $journalRepository->countByHumeur();

    return $this->render('journal/index.html.twig', [
        'journals' => $journals,
        'stats' => $stats,
        'keyword' => $keyword,
        'sort' => $sort,
    ]);
}



    #[Route('/new', name: 'app_journal_new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $entityManager): Response
{
    $journal = new Journal();
    $form = $this->createForm(JournalType::class, $journal);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

        // ✅ AJOUT ICI
        $journal->setUser($this->getUser());
        $journal->setDateCreation(new \DateTime());

        $entityManager->persist($journal);
        $entityManager->flush();

        return $this->redirectToRoute('app_journal_index', [], Response::HTTP_SEE_OTHER);
    }

    return $this->render('journal/new.html.twig', [
        'journal' => $journal,
        'form' => $form,
    ]);
}


    #[Route('/{id}', name: 'app_journal_show', methods: ['GET'])]
    public function show(Journal $journal): Response
    {
        return $this->render('journal/show.html.twig', [
            'journal' => $journal,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_journal_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Journal $journal, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(JournalType::class, $journal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_journal_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('journal/edit.html.twig', [
            'journal' => $journal,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_journal_delete', methods: ['POST'])]
    public function delete(Request $request, Journal $journal, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$journal->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($journal);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_journal_index', [], Response::HTTP_SEE_OTHER);
    }
}
