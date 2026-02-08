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
}
