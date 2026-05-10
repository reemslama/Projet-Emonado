<?php

namespace App\Controller\Admin;

use App\Entity\TestPsyScene;
use App\Form\TestPsySceneType;
use App\Repository\TestPsySceneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestPsySceneController extends AbstractController
{
    #[Route('/admin/enfant/scenes', name: 'admin_test_psy_scene_index', methods: ['GET'])]
    public function index(TestPsySceneRepository $repository): Response
    {
        return $this->render('admin/test_psy_scene/index.html.twig', [
            'scenes' => $repository->findBy([], ['numero' => 'ASC']),
        ]);
    }

    #[Route('/admin/enfant/scenes/new', name: 'admin_test_psy_scene_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $scene = new TestPsyScene();
        $form = $this->createForm(TestPsySceneType::class, $scene);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($scene);
            $em->flush();

            $this->addFlash('success', 'Scène enfant créée avec succès !');
            return $this->redirectToRoute('admin_test_psy_scene_index');
        }

        return $this->render('admin/test_psy_scene/new.html.twig', [
            'scene' => $scene,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/enfant/scenes/edit/{id}', name: 'admin_test_psy_scene_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TestPsyScene $scene, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(TestPsySceneType::class, $scene);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Scène enfant modifiée avec succès !');
            return $this->redirectToRoute('admin_test_psy_scene_index');
        }

        return $this->render('admin/test_psy_scene/edit.html.twig', [
            'scene' => $scene,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/enfant/scenes/delete/{id}', name: 'admin_test_psy_scene_delete', methods: ['POST'])]
    public function delete(Request $request, TestPsyScene $scene, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$scene->getId(), $request->request->get('_token'))) {
            $em->remove($scene);
            $em->flush();
            $this->addFlash('success', 'Scène enfant supprimée avec succès !');
        }

        return $this->redirectToRoute('admin_test_psy_scene_index');
    }
}
