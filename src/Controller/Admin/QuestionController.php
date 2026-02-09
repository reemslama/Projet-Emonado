<?php

namespace App\Controller\Admin;

use App\Entity\Question;
use App\Form\QuestionType;
use App\Repository\QuestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/question')]
final class QuestionController extends AbstractController
{
    // Vérifier l'authentification avant chaque action
    private function checkAuth(SessionInterface $session): ?Response
    {
        if (!$session->get('admin_authenticated')) {
            return $this->redirectToRoute('admin_login');
        }
        return null;
    }

    #[Route(name: 'app_question_index', methods: ['GET'])]
    public function index(QuestionRepository $questionRepository, SessionInterface $session): Response
    {
        if ($redirect = $this->checkAuth($session)) {
            return $redirect;
        }

        return $this->render('admin/question/index.html.twig', [
            'questions' => $questionRepository->findAll(),
            'admin_username' => $session->get('admin_username')
        ]);
    }

    #[Route('/new', name: 'app_question_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SessionInterface $session): Response
    {
        if ($redirect = $this->checkAuth($session)) {
            return $redirect;
        }

        $question = new Question();
        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($question);
            $entityManager->flush();

            $this->addFlash('success', 'Question créée avec succès !');

            return $this->redirectToRoute('app_question_index');
        }

        return $this->render('admin/question/new.html.twig', [
            'question' => $question,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_question_show', methods: ['GET'])]
    public function show(Question $question, SessionInterface $session): Response
    {
        if ($redirect = $this->checkAuth($session)) {
            return $redirect;
        }

        return $this->render('admin/question/show.html.twig', [
            'question' => $question,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_question_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Question $question, EntityManagerInterface $entityManager, SessionInterface $session): Response
    {
        if ($redirect = $this->checkAuth($session)) {
            return $redirect;
        }

        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Question modifiée avec succès !');

            return $this->redirectToRoute('app_question_index');
        }

        return $this->render('admin/question/edit.html.twig', [
            'question' => $question,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_question_delete', methods: ['POST'])]
    public function delete(Request $request, Question $question, EntityManagerInterface $entityManager, SessionInterface $session): Response
    {
        if ($redirect = $this->checkAuth($session)) {
            return $redirect;
        }

        if ($this->isCsrfTokenValid('delete'.$question->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($question);
            $entityManager->flush();

            $this->addFlash('success', 'Question supprimée avec succès !');
        }

        return $this->redirectToRoute('app_question_index');
    }
}