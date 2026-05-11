<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Question;
use App\Entity\Reponse;
use App\Form\QuestionType;
use App\Form\ReponseStandaloneType;
use App\Repository\UserRepository;
use App\Repository\QuestionRepository;
use App\Repository\ReponseRepository;
use App\Repository\AuditLogRepository;
use App\Repository\ConsultationRepository;
use App\Repository\DossierMedicalRepository;
use App\Repository\RendezVousRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AdminController extends AbstractController
{
    private const PASSWORD_MIN_LENGTH = 6;

    // ==================== DASHBOARD UNIFIÉ ====================
    #[Route('/admin', name: 'admin_dashboard')]
    public function dashboard(Request $request, UserRepository $userRepo, QuestionRepository $questionRepo, DossierMedicalRepository $dossierRepo, ConsultationRepository $consultationRepo): Response
    {
        $patients = $userRepo->findByRole('ROLE_PATIENT');
        $psychologues = $userRepo->findByRole('ROLE_PSYCHOLOGUE');

        $categorieFilter = (string) $request->query->get('categorie', 'all');
        $searchKeyword   = trim((string) $request->query->get('search', ''));

        // ✅ FIX : Récupérer les questions SANS cache
        if (!empty($searchKeyword)) {
            $questions = $questionRepo->searchByKeyword($searchKeyword);
        } elseif ($categorieFilter === 'all') {
            // ✅ Force une requête fraîche sans cache Doctrine
            $questions = $questionRepo->createQueryBuilder('q')
                ->orderBy('q.ordre', 'ASC')
                ->getQuery()
                ->getResult();
        } else {
            $questions = $questionRepo->findBy(['categorie' => $categorieFilter], ['ordre' => 'ASC']);
        }

        // ✅ FIX : Extraire les catégories correctement depuis des objets Doctrine
        $allQuestions = $questionRepo->findAll();
        $categories = [];
        foreach ($allQuestions as $q) {
            $cat = $q->getCategorie();
            if ($cat !== null && $cat !== '') {
                $categories[] = $cat;
            }
        }
        $categories = array_unique($categories);
        sort($categories);

        $dossiersParPatient = [];
        foreach ($dossierRepo->findAll() as $dossier) {
            $patient = $dossier->getPatient();
            if ($patient === null) {
                continue;
            }
            $patientId = $patient->getId();
            if ($patientId === null) {
                continue;
            }
            $patientKey = (string) $patientId;
            $dossiersParPatient[$patientKey] = [
                'dossier' => $dossier,
                'consultations' => $dossier->getConsultations()->toArray(),
            ];
        }

        // Statistiques anonymisées (audit / conformité)
        $nbDossiersActifs = $dossierRepo->count([]);
        $debutSemaine = (new \DateTime())->setTimestamp(strtotime('monday this week'));
        $finSemaine = (clone $debutSemaine)->modify('+7 days');
        $nbConsultationsSemaine = $consultationRepo->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.createdAt >= :debut')
            ->andWhere('c.createdAt < :fin')
            ->setParameter('debut', $debutSemaine)
            ->setParameter('fin', $finSemaine)
            ->getQuery()
            ->getSingleScalarResult();
        $debutAujourdhui = (new \DateTime())->setTime(0, 0, 0);
        $nbConsultationsAujourdhui = $consultationRepo->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.createdAt >= :debut')
            ->setParameter('debut', $debutAujourdhui)
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('admin/index.html.twig', [
            'patients'           => $patients,
            'psychologues'       => $psychologues,
            'questions'          => $questions,
            'categories'         => $categories,
            'categorieFilter'    => $categorieFilter,
            'searchKeyword'      => $searchKeyword,
            'dossiersParPatient' => $dossiersParPatient,
            'stats'              => [
                'dossiers_actifs' => $nbDossiersActifs,
                'consultations_semaine' => $nbConsultationsSemaine,
                'consultations_aujourdhui' => $nbConsultationsAujourdhui,
            ],
        ]);
    }

    #[Route('/admin/questions/search', name: 'admin_questions_search', methods: ['GET'])]
    public function questionsSearch(Request $request, QuestionRepository $questionRepo): Response
    {
        $search   = trim((string) $request->query->get('search', ''));
        $category = (string) $request->query->get('categorie', 'all');
        $sortBy   = (string) $request->query->get('sortBy', 'ordre');
        $sortOrder = strtoupper((string) $request->query->get('sortOrder', 'ASC'));

        // Sécurité sur le tri
        if (!in_array($sortBy, ['id', 'texte', 'categorie', 'ordre'], true)) {
            $sortBy = 'ordre';
        }
        if (!in_array($sortOrder, ['ASC', 'DESC'], true)) {
            $sortOrder = 'ASC';
        }

        $qb = $questionRepo->createQueryBuilder('q');

        if ($search !== '') {
            $qb->andWhere('LOWER(q.texte) LIKE LOWER(:keyword) OR LOWER(q.categorie) LIKE LOWER(:keyword)')
               ->setParameter('keyword', '%' . strtolower($search) . '%');
        }

        if ($category !== 'all') {
            $qb->andWhere('q.categorie = :cat')
               ->setParameter('cat', $category);
        }

        $questions = $qb->orderBy('q.' . $sortBy, $sortOrder)
                        ->getQuery()
                        ->getResult();

        return $this->render('admin/_questions_list.html.twig', [
            'questions'       => $questions,
            'searchKeyword'   => $search,
            'categorieFilter' => $category,
        ]);
    }

    #[Route('/admin/logs', name: 'admin_logs')]
    public function logs(AuditLogRepository $auditLogRepo): Response
    {
        $logs = $auditLogRepo->findRecent(200);
        return $this->render('admin/logs.html.twig', [
            'logs' => $logs,
        ]);
    }

    // ==================== AJOUT STATISTIQUES RDV ====================
    #[Route('/admin/statistiques/rdv', name: 'admin_stats_rdv')]
public function statsRdv(RendezVousRepository $rdvRepo): Response
{
    $stats = $rdvRepo->getStatsParMois();

    return $this->render('admin/stats_rdv.html.twig', [ // ✅ Template admin
        'stats' => $stats
    ]);
}

    // ==================== AJOUT UTILISATEUR ====================
    #[Route('/admin/user/add', name: 'admin_user_add', methods: ['GET', 'POST'])]
    public function add(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepo
    ): Response {
        $errors = [];
        $formData = [
            'nom'        => '',
            'prenom'     => '',
            'email'      => '',
            'telephone'  => '',
            'specialite' => '',
            'role'       => 'ROLE_PATIENT',
        ];

        if ($request->isMethod('POST')) {
            $nom        = trim((string) $request->request->get('nom', ''));
            $prenom     = trim((string) $request->request->get('prenom', ''));
            $email      = trim((string) $request->request->get('email', ''));
            $telephone  = trim((string) $request->request->get('telephone', ''));
            $specialite = trim((string) $request->request->get('specialite', ''));
            $role       = (string) $request->request->get('role', 'ROLE_PATIENT');
            $password   = (string) $request->request->get('password', '');

            $formData = compact('nom', 'prenom', 'email', 'telephone', 'specialite', 'role');

            if ($nom === '')     $errors[] = 'Le nom est obligatoire.';
            if ($prenom === '')  $errors[] = 'Le prénom est obligatoire.';
            if ($email === '')   $errors[] = 'L\'email est obligatoire.';
            elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'L\'email n\'est pas valide.';
            if ($password === '') $errors[] = 'Le mot de passe est obligatoire.';
            elseif (strlen($password) < self::PASSWORD_MIN_LENGTH) {
                $errors[] = 'Le mot de passe doit contenir au moins ' . self::PASSWORD_MIN_LENGTH . ' caractères.';
            }
            if (!in_array($role, ['ROLE_PATIENT', 'ROLE_PSYCHOLOGUE'], true)) {
                $errors[] = 'Le rôle choisi n\'est pas valide.';
            }

            if (empty($errors)) {
                $existing = $userRepo->findOneBy(['email' => $email]);
                if ($existing) {
                    $errors[] = 'Cet email est déjà utilisé.';
                }
            }

            if (empty($errors)) {
                $user = new User();
                $user->setNom($nom);
                $user->setPrenom($prenom);
                $user->setEmail($email);
                $user->setTelephone($telephone ?: null);
                $user->setSpecialite($specialite ?: null);
                $user->setRoles([$role]);
                $user->setPassword($passwordHasher->hashPassword($user, $password));

                try {
                    $em->persist($user);
                    $em->flush();
                    $this->addFlash('success', 'Utilisateur ajouté avec succès.');
                    return $this->redirectToRoute('admin_dashboard');
                } catch (UniqueConstraintViolationException $e) {
                    $errors[] = 'Cet email est déjà utilisé.';
                } catch (\Exception $e) {
                    $errors[] = 'Erreur serveur : ' . $e->getMessage();
                }
            }
        }

        return $this->render('admin/add.html.twig', [
            'errors'   => $errors,
            'formData' => $formData,
        ]);
    }

    // ==================== ÉDITION UTILISATEUR ====================
    #[Route('/admin/user/edit/{id}', name: 'admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(
        User $user,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepo
    ): Response {
        if ($request->isMethod('POST')) {
            $user->setNom($request->request->get('nom'));
            $user->setPrenom($request->request->get('prenom'));
            $user->setTelephone($request->request->get('telephone'));
            $user->setSpecialite($request->request->get('specialite'));

            $password = $request->request->get('password');
            if ($password) {
                $user->setPassword($passwordHasher->hashPassword($user, $password));
            }

            try {
                $em->flush();
                $this->addFlash('success', 'Utilisateur modifié avec succès !');
                return $this->redirectToRoute('admin_dashboard');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la modification : ' . $e->getMessage());
            }
        }

        return $this->render('admin/edit.html.twig', [
            'user' => $user,
        ]);
    }

    // ==================== SUPPRESSION UTILISATEUR ====================
    #[Route('/admin/user/delete/{id}', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(User $user, EntityManagerInterface $em): Response
    {
        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'Utilisateur supprimé avec succès !');
        return $this->redirectToRoute('admin_dashboard');
    }
}
