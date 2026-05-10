<?php

namespace App\Controller;

use App\Entity\TestPsyScene;
use App\Entity\TestPsyReponse;
use App\Entity\TestPsyParticipation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EspaceEnfantController extends AbstractController
{
    #[Route('/espace-enfant', name: 'app_espace_enfant')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        return $this->render('espace_enfant/index.html.twig');
    }

    #[Route('/espace-enfant/commencer', name: 'app_espace_enfant_start')]
    public function start(EntityManagerInterface $entityManager, Request $request): Response
    {
        $session = $request->getSession();
        
        // Créer une nouvelle participation
        $participation = new TestPsyParticipation();
        $participation->setUser($this->getUser());
        
        $entityManager->persist($participation);
        $entityManager->flush();

        $session->set('participation_enfant_id', $participation->getId());

        // Récupérer la première scène
        $premiereScene = $entityManager->getRepository(TestPsyScene::class)->findOneBy(['actif' => true], ['numero' => 'ASC']);

        if (!$premiereScene) {
            $this->addFlash('error', 'Aucune scène de test disponible.');
            return $this->redirectToRoute('app_espace_enfant');
        }

        return $this->redirectToRoute('app_espace_enfant_scene', ['id' => $premiereScene->getId()]);
    }

    #[Route('/espace-enfant/scene/{id}', name: 'app_espace_enfant_scene')]
    public function scene(TestPsyScene $scene, EntityManagerInterface $entityManager, Request $request): Response
    {
        $participationId = $request->getSession()->get('participation_enfant_id');
        if (!$participationId) {
            return $this->redirectToRoute('app_espace_enfant');
        }

        return $this->render('espace_enfant/scene.html.twig', [
            'scene' => $scene,
        ]);
    }

    #[Route('/espace-enfant/repondre/{sceneId}/{reponseId}', name: 'app_espace_enfant_answer')]
    public function answer(int $sceneId, int $reponseId, EntityManagerInterface $entityManager, Request $request): Response
    {
        $participationId = $request->getSession()->get('participation_enfant_id');
        if (!$participationId) {
            return $this->redirectToRoute('app_espace_enfant');
        }

        $participation = $entityManager->getRepository(TestPsyParticipation::class)->find($participationId);
        $reponse = $entityManager->getRepository(TestPsyReponse::class)->find($reponseId);

        if ($participation && $reponse) {
            // Insérer dans la table de jointure avec scene_id (nécessaire pour la contrainte d'intégrité)
            $entityManager->getConnection()->executeStatement(
                'INSERT INTO test_psy_reponse_enfant (participation_id, scene_id, reponse_id) VALUES (?, ?, ?)',
                [$participation->getId(), $sceneId, $reponseId]
            );
            
            // Mettre à jour les scores cumulés
            $participation->setScoreAnxiete($participation->getScoreAnxiete() + $reponse->getPoidsAnxiete());
            $participation->setScoreTristesse($participation->getScoreTristesse() + $reponse->getPoidsTristesse());
            $participation->setScoreColere($participation->getScoreColere() + $reponse->getPoidsColere());
            $participation->setScoreJoie($participation->getScoreJoie() + $reponse->getPoidsJoie());

            $entityManager->flush();
        }

        // Trouver la scène suivante
        $currentScene = $entityManager->getRepository(TestPsyScene::class)->find($sceneId);
        $nextScene = $entityManager->getRepository(TestPsyScene::class)->createQueryBuilder('s')
            ->where('s.numero > :currentNumero')
            ->andWhere('s.actif = true')
            ->setParameter('currentNumero', $currentScene->getNumero())
            ->orderBy('s.numero', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($nextScene) {
            return $this->redirectToRoute('app_espace_enfant_scene', ['id' => $nextScene->getId()]);
        }

        return $this->redirectToRoute('app_espace_enfant_resultat');
    }

    #[Route('/espace-enfant/resultat', name: 'app_espace_enfant_resultat')]
    public function resultat(EntityManagerInterface $entityManager, Request $request): Response
    {
        $participationId = $request->getSession()->get('participation_enfant_id');
        if (!$participationId) {
            return $this->redirectToRoute('app_espace_enfant');
        }

        $participation = $entityManager->getRepository(TestPsyParticipation::class)->find($participationId);

        // Nettoyer la session
        $request->getSession()->remove('participation_enfant_id');

        return $this->render('espace_enfant/resultat.html.twig', [
            'participation' => $participation,
        ]);
    }
}
