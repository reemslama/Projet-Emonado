<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        // On indique le chemin complet depuis templates/
        return $this->render('home/index.html.twig');
    }

    #[Route('/test', name: 'test_page')]
    public function test(): Response
    {
        return $this->render('test/index.html.twig'); 
    }
<<<<<<< HEAD
=======

    #[Route('/favicon.ico', name: 'app_favicon', methods: ['GET'])]
    public function favicon(): BinaryFileResponse
    {
        $projectDirParam = $this->getParameter('kernel.project_dir');
        $projectDir = is_string($projectDirParam) ? $projectDirParam : '';
        $iconPath = $projectDir . '/public/images/logo.png';

        $response = new BinaryFileResponse($iconPath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, 'favicon.ico');
        $response->headers->set('Content-Type', 'image/png');
        $response->setPublic();
        $response->setMaxAge(86400);

        return $response;
    }
>>>>>>> d9465e5 (finalVersionByTeam)
}
