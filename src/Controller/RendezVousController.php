<?php

namespace App\Controller;

use App\Entity\RendezVous;
use App\Form\RendezVousType;
use App\Repository\RendezVousRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/rendez-vous')]
class RendezVousController extends AbstractController
{
    #[Route('/', name: 'app_rendez_vous_index', methods: ['GET'])]
    public function index(Request $request, RendezVousRepository $repo): Response 
    {
        $search = $request->query->get('q');
        $sort = $request->query->get('sort', 'date');
        
        $rendezVous = $repo->findBySearchAndSort($search, $sort);

        return $this->render('rendez_vous/index.html.twig', [
            'rendez_vous' => $rendezVous,  // ✅ Nom cohérent avec le template
            'search' => $search,
            'sort' => $sort
        ]);
    }

    #[Route('/new', name: 'app_rendez_vous_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response 
    {
        $rdv = new RendezVous();
        $form = $this->createForm(RendezVousType::class, $rdv);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($rdv);
            $em->flush();
            
            $this->addFlash('success', 'Rendez-vous créé avec succès !');
            return $this->redirectToRoute('app_rendez_vous_index');
        }
        
        return $this->render('rendez_vous/new.html.twig', [
            'form' => $form->createView(),
            'rdv' => $rdv
        ]);
    }

    #[Route('/{id}/edit', name: 'app_rendez_vous_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, RendezVous $rdv, EntityManagerInterface $em): Response 
    {
        $form = $this->createForm(RendezVousType::class, $rdv);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Rendez-vous modifié avec succès !');
            return $this->redirectToRoute('app_rendez_vous_index');
        }
        
        return $this->render('rendez_vous/edit.html.twig', [
            'form' => $form->createView(),
            'rdv' => $rdv
        ]);
    }

    #[Route('/{id}', name: 'app_rendez_vous_delete', methods: ['POST'])]
    public function delete(Request $request, RendezVous $rdv, EntityManagerInterface $em): Response 
    {
        if ($this->isCsrfTokenValid('delete'.$rdv->getId(), $request->request->get('_token'))) {
            $em->remove($rdv);
            $em->flush();
            $this->addFlash('success', 'Rendez-vous supprimé avec succès !');
        }
        return $this->redirectToRoute('app_rendez_vous_index');
    }

    #[Route('/{id}', name: 'app_rendez_vous_show', methods: ['GET'])]
    public function show(RendezVous $rdv): Response 
    {
        return $this->render('rendez_vous/show.html.twig', [
            'rdv' => $rdv
        ]);
    }
}