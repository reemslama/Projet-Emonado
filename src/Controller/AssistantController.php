<?php

namespace App\Controller;

use App\Service\GeminiAssistant;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AssistantController extends AbstractController
{
    #[Route('/assistant', name: 'app_assistant')]
    public function index(): Response
    {
        return $this->render('assistant/index.html.twig');
    }

    #[Route('/assistant/ask', name: 'app_assistant_ask', methods: ['POST'])]
    public function ask(Request $request, GeminiAssistant $assistant): Response
    {
        $question = $request->request->get('question', '');
        
        if (empty($question)) {
            return $this->json(['erreur' => 'Veuillez poser une question']);
        }
        
        $resultat = $assistant->suggererCreneaux($question);
        
        return $this->json($resultat);
    }
}