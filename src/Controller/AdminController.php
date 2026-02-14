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
    public function dashboard(Request $request, UserRepository $userRepo, QuestionRepository $questionRepo): Response
    {
        // Récupération des utilisateurs
        if (method_exists($userRepo, 'findByRole')) {
            $patients     = $userRepo->findByRole('ROLE_PATIENT');
            $psychologues = $userRepo->findByRole('ROLE_PSYCHOLOGUE');
        } else {
            $all          = $userRepo->findAll();
            $patients     = array_filter($all, fn(User $u) => in_array('ROLE_PATIENT', $u->getRoles(), true));
            $psychologues = array_filter($all, fn(User $u) => in_array('ROLE_PSYCHOLOGUE', $u->getRoles(), true) || in_array('ROLE_PSY', $u->getRoles(), true));
        }

        $categorieFilter = $request->query->get('categorie', 'all');
        $searchKeyword   = trim($request->query->get('search', ''));

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

        return $this->render('admin/index.html.twig', [
            'patients'       => $patients,
            'psychologues'   => $psychologues,
            'questions'      => $questions,
            'categories'     => $categories,
            'categorieFilter'=> $categorieFilter,
            'searchKeyword'  => $searchKeyword,
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
            $email = trim((string) $request->request->get('email'));

            if ($email !== $user->getEmail()) {
                $existing = $userRepo->findOneBy(['email' => $email]);
                if ($existing && $existing->getId() !== $user->getId()) {
                    $this->addFlash('error', 'Cet email est déjà utilisé par un autre utilisateur.');
                    return $this->render('admin/edit.html.twig', [
                        'user'     => $user,
                        'formData' => $request->request->all()
                    ]);
                }
                $user->setEmail($email);
            }

            $user->setNom((string) $request->request->get('nom'));
            $user->setPrenom((string) $request->request->get('prenom'));
            $user->setTelephone($request->request->get('telephone') ?: null);
            $user->setSpecialite($request->request->get('specialite') ?: null);

            $role = (string) $request->request->get('role');
            if ($role && in_array($role, ['ROLE_PATIENT', 'ROLE_PSYCHOLOGUE'])) {
                $user->setRoles([$role]);
            }

            $password = trim((string) $request->request->get('password'));
            if ($password !== '') {
                if (strlen($password) < self::PASSWORD_MIN_LENGTH) {
                    $this->addFlash('error', 'Le mot de passe doit contenir au moins ' . self::PASSWORD_MIN_LENGTH . ' caractères.');
                } else {
                    $user->setPassword($passwordHasher->hashPassword($user, $password));
                }
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
    public function deleteUser(
        User $user,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            try {
                $em->remove($user);
                $em->flush();
                $this->addFlash('success', 'Utilisateur supprimé avec succès !');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('admin_dashboard');
    }

    // ==================== GESTION QUESTIONS ====================

    #[Route('/admin/question/new', name: 'app_question_new', methods: ['GET', 'POST'])]
    public function questionNew(
        Request $request,
        EntityManagerInterface $em,
        QuestionRepository $questionRepo
    ): Response {
        $question = new Question();
        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($questionRepo->existsByTexte($question->getTexte())) {
                $this->addFlash('error', 'Une question avec ce texte existe déjà.');
            } else {
                try {
                    // ✅ AUTO-ASSIGNER L'ORDRE DE LA QUESTION
                    if ($question->getOrdre() === null || $question->getOrdre() === 0) {
                        $maxOrdre = $questionRepo->createQueryBuilder('q')
                            ->select('MAX(q.ordre)')
                            ->getQuery()
                            ->getSingleScalarResult();
                        $question->setOrdre(($maxOrdre ?? 0) + 1);
                    }
                    
                    // ✅ AUTO-ASSIGNER L'ORDRE DES RÉPONSES
                    $index = 1;
                    foreach ($question->getReponses() as $reponse) {
                        if ($reponse->getOrdre() === null || $reponse->getOrdre() === 0) {
                            $reponse->setOrdre($index++);
                        }
                    }
                    
                    $em->persist($question);
                    $em->flush();
                    $em->clear();
                    $this->addFlash('success', 'Question créée avec succès !');
                    return $this->redirectToRoute('admin_dashboard');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'enregistrement : ' . $e->getMessage());
                }
            }
        }

        return $this->render('admin/question/new.html.twig', [
            'question' => $question,
            'form'     => $form,
        ]);
    }

    #[Route('/admin/question/{id}/edit', name: 'app_question_edit', methods: ['GET', 'POST'])]
    public function questionEdit(
        Request $request,
        Question $question,
        EntityManagerInterface $em
    ): Response {
        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', 'Question modifiée avec succès !');
                return $this->redirectToRoute('admin_dashboard');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'enregistrement : ' . $e->getMessage());
            }
        }

        return $this->render('admin/question/edit.html.twig', [
            'question' => $question,
            'form'     => $form,
        ]);
    }

    #[Route('/admin/question/{id}/delete', name: 'app_question_delete', methods: ['POST'])]
    public function questionDelete(Request $request, Question $question, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $question->getId(), $request->request->get('_token'))) {
            try {
                $em->remove($question);
                $em->flush();
                $this->addFlash('success', 'Question supprimée avec succès !');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/admin/question/{id}', name: 'app_question_show', methods: ['GET'])]
    public function questionShow(Question $question): Response
    {
        return $this->render('admin/question/show.html.twig', [
            'question' => $question,
        ]);
    }

    // ==================== GESTION RÉPONSES ====================

    #[Route('/admin/reponse', name: 'admin_reponse_index', methods: ['GET'])]
    public function reponseIndex(ReponseRepository $reponseRepo, Request $request): Response
    {
        $searchKeyword = trim($request->query->get('search', ''));
        
        if (!empty($searchKeyword)) {
            // Recherche dans le texte des réponses
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
    public function reponseNew(
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $reponse = new Reponse();
        $form = $this->createForm(ReponseStandaloneType::class, $reponse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // ✅ AUTO-ASSIGNER L'ORDRE SI NULL
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
                $em->clear();
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
    public function reponseEdit(
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
    public function reponseDelete(Request $request, Reponse $reponse, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $reponse->getId(), $request->request->get('_token'))) {
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
    public function reponseShow(Reponse $reponse): Response
    {
        return $this->render('admin/reponse/show.html.twig', [
            'reponse' => $reponse,
        ]);
    }
}