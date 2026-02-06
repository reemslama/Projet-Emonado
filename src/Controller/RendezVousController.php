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

#[Route('/rendez/vous')]
class RendezVousController extends AbstractController
{
    #[Route('/', name: 'app_rendez_vous_index', methods: ['GET'])]
    public function index(RendezVousRepository $repository, Request $request): Response
    {
        $search = $request->query->get('search');
        // Tri DESC par date pour voir les nouveaux en premier
        $rendez_vous = $search 
            ? $repository->findBy(['nom_patient' => $search], ['date' => 'DESC'])
            : $repository->findBy([], ['date' => 'DESC']);

        return $this->render('rendez_vous/index.html.twig', ['rendez_vous' => $rendez_vous]);
    }

    #[Route('/accueil', name: 'app_front_home', methods: ['GET'])]
    public function frontHome(RendezVousRepository $repository, Request $request): Response
    {
        $cinSaisi = $request->query->get('cin');
        $rendez_vous = $cinSaisi ? $repository->findBy(['cin' => $cinSaisi], ['date' => 'DESC']) : [];

        return $this->render('client/front.html.twig', [
            'rendez_vous' => $rendez_vous,
            'cinSaisi' => $cinSaisi
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
            return $this->redirectToRoute('app_rendez_vous_index');
        }
        return $this->render('rendez_vous/new.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/{id}/edit', name: 'app_rendez_vous_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, RendezVous $rdv, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(RendezVousType::class, $rdv);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('app_rendez_vous_index');
        }
        return $this->render('rendez_vous/edit.html.twig', [
            'form' => $form->createView(), 
            'rendez_vou' => $rdv
        ]);
    }

    #[Route('/{id}', name: 'app_rendez_vous_delete', methods: ['POST'])]
    public function delete(Request $request, RendezVous $rdv, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$rdv->getId(), $request->request->get('_token'))) {
            $em->remove($rdv);
            $em->flush();
        }
        return $this->redirectToRoute('app_rendez_vous_index');
    }
}