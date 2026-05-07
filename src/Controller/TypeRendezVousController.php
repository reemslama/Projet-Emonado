<?php

namespace App\Controller;

use App\Entity\TypeRendezVous;
use App\Form\TypeRendezVousType;
use App\Repository\TypeRendezVousRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/type/rendez-vous')]
class TypeRendezVousController extends AbstractController
{
    #[Route('/', name: 'app_type_rendez_vous_index', methods: ['GET'])]
    public function index(TypeRendezVousRepository $repo): Response
    {
        $types = $repo->findAll();

        return $this->render('type_rendez_vous/index.html.twig', [
            'types' => $types,
        ]);
    }

    #[Route('/new', name: 'app_type_rendez_vous_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $type = new TypeRendezVous();
        $form = $this->createForm(TypeRendezVousType::class, $type);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($type);
            $em->flush();
            
            $this->addFlash('success', 'Type de rendez-vous créé avec succès !');
            return $this->redirectToRoute('app_type_rendez_vous_index');
        }

        return $this->render('type_rendez_vous/new.html.twig', [
            'form' => $form->createView(),
            'type' => $type
        ]);
    }
}