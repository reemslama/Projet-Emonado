<?php

namespace App\Controller\Admin;

use App\Entity\Reponse;
use App\Form\ReponseStandaloneType;
use App\Repository\ReponseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ReponseController extends AbstractController
{
    #[Route('/admin/reponse', name: 'admin_reponse_index', methods: ['GET'])]
    public function index(ReponseRepository $reponseRepo, Request $request): Response
    {
        $searchKeyword = trim($request->query->get('search', ''));
        
        if (!empty($searchKeyword)) {
            $reponses = $reponseRepo->createQueryBuilder('r')
                ->leftJoin('r.question', 'q')
                ->where('r.texte LIKE :keyword')
                ->orWhere('q.texte LIKE :keyword')
                ->setParameter('keyword', '%' . $searchKeyword . '%')
                ->orderBy('r.id', 'ASC')
                ->getQuery()
                ->getResult();
        } else {
            $reponses = $reponseRepo->findAll();
        }

        return $this->render('admin/reponse/index.html.twig', [
            'reponses' => $reponses,
            'searchKeyword' => $searchKeyword,
        ]);
    }

    #[Route('/admin/reponse/new', name: 'admin_reponse_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $reponse = new Reponse();
        $form = $this->createForm(ReponseStandaloneType::class, $reponse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                if ($reponse->getOrdre() === null) {
                    $question = $reponse->getQuestion();
                    if ($question) {
                        $maxOrdre = 0;
                        foreach ($question->getReponses() as $r) {
                            if ($r->getOrdre() > $maxOrdre) {
                                $maxOrdre = $r->getOrdre();
                            }
                        }
                        $reponse->setOrdre($maxOrdre + 1);
                    } else {
                        $reponse->setOrdre(1);
                    }
                }
                
                $em->persist($reponse);
                $em->flush();
                $this->addFlash('success', 'Réponse créée avec succès !');
                return $this->redirectToRoute('admin_reponse_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'enregistrement : ' . $e->getMessage());
            }
        }

        return $this->render('admin/reponse/new.html.twig', [
            'reponse' => $reponse,
            'form' => $form,
        ]);
    }

    #[Route('/admin/reponse/{id}/edit', name: 'admin_reponse_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Reponse $reponse,
        EntityManagerInterface $em
    ): Response {
        $form = $this->createForm(ReponseStandaloneType::class, $reponse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', 'Réponse modifiée avec succès !');
                return $this->redirectToRoute('admin_reponse_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'enregistrement : ' . $e->getMessage());
            }
        }

        return $this->render('admin/reponse/edit.html.twig', [
            'reponse' => $reponse,
            'form' => $form,
        ]);
    }

    #[Route('/admin/reponse/{id}/delete', name: 'admin_reponse_delete', methods: ['POST'])]
    public function delete(Request $request, Reponse $reponse, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $reponse->getId(), (string) $request->request->get('_token'))) {
            try {
                $em->remove($reponse);
                $em->flush();
                $this->addFlash('success', 'Réponse supprimée avec succès !');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('admin_reponse_index');
    }

    #[Route('/admin/reponse/{id}', name: 'admin_reponse_show', methods: ['GET'])]
    public function show(Reponse $reponse): Response
    {
        return $this->render('admin/reponse/show.html.twig', [
            'reponse' => $reponse,
        ]);
    }
}
