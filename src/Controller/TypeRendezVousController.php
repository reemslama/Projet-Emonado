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
    public function index(TypeRendezVousRepository $repository): Response
    {
        return $this->render('type_rendez_vous/index.html.twig', [
            'types' => $repository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_type_rendez_vous_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $typeRendezVous = new TypeRendezVous();
        $form = $this->createForm(TypeRendezVousType::class, $typeRendezVous);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($typeRendezVous);
            $entityManager->flush();

            $this->addFlash('success', 'Type de rendez-vous créé avec succès !');
            return $this->redirectToRoute('app_type_rendez_vous_index');
        }

        return $this->render('type_rendez_vous/new.html.twig', [
            'form' => $form->createView(),
            'type' => $typeRendezVous
        ]);
    }

    #[Route('/{id}', name: 'app_type_rendez_vous_show', methods: ['GET'])]
    public function show(TypeRendezVous $typeRendezVous): Response
    {
        return $this->render('type_rendez_vous/show.html.twig', [
            'type' => $typeRendezVous
        ]);
    }

    #[Route('/{id}/edit', name: 'app_type_rendez_vous_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TypeRendezVous $typeRendezVous, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TypeRendezVousType::class, $typeRendezVous);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Type de rendez-vous modifié avec succès !');
            return $this->redirectToRoute('app_type_rendez_vous_index');
        }

        return $this->render('type_rendez_vous/edit.html.twig', [
            'form' => $form->createView(),
            'type' => $typeRendezVous
        ]);
    }

    #[Route('/{id}', name: 'app_type_rendez_vous_delete', methods: ['POST'])]
    public function delete(Request $request, TypeRendezVous $typeRendezVous, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $typeRendezVous->getId(), $request->request->get('_token'))) {
            $entityManager->remove($typeRendezVous);
            $entityManager->flush();
            $this->addFlash('success', 'Type de rendez-vous supprimé avec succès !');
        }

        return $this->redirectToRoute('app_type_rendez_vous_index');
    }
}