<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ReponseController extends AbstractController
{
    public function index(): Response
    {
        return $this->render('admin/reponse/index.html.twig');
    }
}
