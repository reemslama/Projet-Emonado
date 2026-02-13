<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig');
    }

    #[Route('/test', name: 'test_page')]
    public function test(): Response
    {
        // Redirige vers le template de choix du questionnaire
        return $this->render('questionnaire/choix.html.twig'); 
    }

    #[Route('/favicon.ico', name: 'app_favicon', methods: ['GET'])]
    public function favicon(): BinaryFileResponse
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        $iconPath = $projectDir . '/public/images/logo.png';

        $response = new BinaryFileResponse($iconPath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, 'favicon.ico');
        $response->headers->set('Content-Type', 'image/png');
        $response->setPublic();
        $response->setMaxAge(86400);

        return $response;
    }
}