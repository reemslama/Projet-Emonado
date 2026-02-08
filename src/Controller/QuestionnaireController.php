<?php

namespace App\Controller;

use App\Repository\QuestionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class QuestionnaireController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        return $this->redirectToRoute('app_choix');
    }

    #[Route('/choix', name: 'app_choix')]
    public function choix(): Response
    {
        return $this->render('questionnaire/choix.html.twig');
    }

    #[Route('/test/{categorie}', name: 'app_test')]
    public function test($categorie, QuestionRepository $repo): Response
    {
        $questions = $repo->findBy(
            ['categorie' => $categorie],
            ['ordre' => 'ASC']
        );

        return $this->render('questionnaire/test.html.twig', [
            'questions' => $questions,
            'categorie' => $categorie
        ]);
    }

    #[Route('/resultat', name: 'app_resultat', methods: ['POST'])]
    public function resultat(Request $request): Response
    {
        $score = array_sum($request->request->all());

        return $this->render('questionnaire/resultat.html.twig', [
            'score' => $score
        ]);
    }
}
