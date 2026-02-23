<?php

namespace App\Controller;

use App\Entity\RendezVous;
use App\Form\RendezVousType;
use App\Repository\RendezVousRepository;
use App\Repository\TypeRendezVousRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/rendez-vous')]
class RendezVousController extends AbstractController
{
    // ✅ 1. CALENDRIER BUNDLE (le plus spécifique)
    #[Route('/calendrier-bundle', name: 'app_rendez_vous_calendrier_bundle')]
    public function calendrierBundle(TypeRendezVousRepository $typeRepo): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
        
        $types = $typeRepo->findAll();
        
        return $this->render('rendez_vous/calendrier_bundle.html.twig', [
            'types' => $types
        ]);
    }

    // ✅ 2. INDEX
    #[Route('/', name: 'app_rendez_vous_index', methods: ['GET'])]
    public function index(
        Request $request, 
        RendezVousRepository $repo,
        PaginatorInterface $paginator
    ): Response 
    {
        $search = $request->query->get('q');
        $sort = $request->query->get('sort', 'date');
        
        $queryBuilder = $repo->findBySearchAndSortQueryBuilder($search, $sort);
        
        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            3
        );

        return $this->render('rendez_vous/index.html.twig', [
            'pagination' => $pagination,
            'search' => $search,
            'sort' => $sort
        ]);
    }
    
    // ✅ 3. NOUVEAU
    #[Route('/new', name: 'app_rendez_vous_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response 
    {
        $rdv = new RendezVous();
        
        if ($this->getUser()) {
            $rdv->setPatient($this->getUser());
        }
        
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

    // ✅ 4. HISTORIQUE
    #[Route('/historique', name: 'app_rendez_vous_historique')]
    public function historique(RendezVousRepository $repo): Response
    {
        $patient = $this->getUser();

        if (!in_array('ROLE_PATIENT', $patient->getRoles())) {
            throw $this->createAccessDeniedException('Accès réservé aux patients');
        }

        $rendezVous = $repo->findHistoriqueByPatient($patient);

        return $this->render('rendez_vous/historique.html.twig', [
            'rendez_vous' => $rendezVous
        ]);
    }

    // ✅ 5. STATISTIQUES
    #[Route('/statistiques/mois', name: 'app_rendez_vous_stats_mois')]
    public function statsMois(RendezVousRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PSYCHOLOGUE');

        $stats = $repo->getStatsParMois();

        return $this->render('rendez_vous/stats_mois.html.twig', [
            'stats' => $stats
        ]);
    }

    // ✅ 6. SHOW (route avec paramètre, en dernier)
    #[Route('/{id}', name: 'app_rendez_vous_show', methods: ['GET'])]
    public function show(?RendezVous $rdv): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
        
        if (!$rdv) {
            $this->addFlash('error', 'Ce rendez-vous n\'existe pas ou a été supprimé.');
            return $this->redirectToRoute('app_rendez_vous_index');
        }
        
        return $this->render('rendez_vous/show.html.twig', [
            'rdv' => $rdv
        ]);
    }
}