<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Question;
use App\Form\QuestionType;
use App\Repository\UserRepository;
use App\Repository\QuestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class AdminController extends AbstractController
{
    // ==================== DASHBOARD UNIFIÉ ====================
    #[Route('/admin', name: 'admin_dashboard')]
    public function dashboard(Request $request, UserRepository $userRepo, QuestionRepository $questionRepo): Response
    {
        // Récupération des utilisateurs
        if (method_exists($userRepo, 'findByRole')) {
            $patients = $userRepo->findByRole('ROLE_PATIENT');
            $psychologues = $userRepo->findByRole('ROLE_PSYCHOLOGUE');
        } else {
            $all = $userRepo->findAll();
            $patients = array_filter($all, fn(User $u) => in_array('ROLE_PATIENT', $u->getRoles(), true));
            $psychologues = array_filter($all, fn(User $u) => in_array('ROLE_PSYCHOLOGUE', $u->getRoles(), true) || in_array('ROLE_PSY', $u->getRoles(), true));
        }

        // Récupération du filtre de catégorie
        $categorieFilter = $request->query->get('categorie', 'all');
        
        // *** AJOUT : Récupération du mot-clé de recherche ***
        $searchKeyword = trim($request->query->get('search', ''));
        
        // Filtrage des questions par catégorie ET recherche
        if (!empty($searchKeyword)) {
            // Si recherche active, chercher dans toutes les catégories
            $questions = $questionRepo->searchByKeyword($searchKeyword);
        } elseif ($categorieFilter === 'all') {
            $questions = $questionRepo->findBy([], ['ordre' => 'ASC']);
        } else {
            $questions = $questionRepo->findBy(['categorie' => $categorieFilter], ['ordre' => 'ASC']);
        }

        // Récupération de toutes les catégories disponibles
        $allQuestions = $questionRepo->findAll();
        $categories = [];
        foreach ($allQuestions as $q) {
            $cat = $q->getCategorie();
            if ($cat && !in_array($cat, $categories)) {
                $categories[] = $cat;
            }
        }
        sort($categories);

        return $this->render('admin/index.html.twig', [
            'patients' => $patients,
            'psychologues' => $psychologues,
            'questions' => $questions,
            'categories' => $categories,
            'categorieFilter' => $categorieFilter,
            'searchKeyword' => $searchKeyword, // *** Pour afficher dans le formulaire ***
        ]);
    }

    // *** NOUVELLE ROUTE : Recherche AJAX de questions ***
    #[Route('/admin/questions/search', name: 'admin_questions_search', methods: ['GET'])]
    public function searchQuestions(Request $request, QuestionRepository $questionRepo): Response
    {
        $keyword = trim($request->query->get('q', ''));
        
        if (empty($keyword)) {
            return $this->json([]);
        }
        
        $questions = $questionRepo->searchByKeyword($keyword);
        
        // Retourner en JSON pour AJAX
        $results = [];
        foreach ($questions as $question) {
            $results[] = [
                'id' => $question->getId(),
                'texte' => $question->getTexte(),
                'categorie' => $question->getCategorie(),
                'ordre' => $question->getOrdre(),
            ];
        }
        
        return $this->json($results);
    }

    // ==================== GESTION UTILISATEURS ====================
    #[Route('/admin/user/add', name: 'admin_user_add')]
    public function add(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, UserRepository $userRepo): Response
    {
        if ($request->isMethod('POST')) {
            $email = trim((string) $request->request->get('email'));
            
            // Validation de base
            if (empty($email)) {
                $this->addFlash('error', 'L\'email est obligatoire !');
                return $this->render('admin/add.html.twig', [
                    'formData' => $request->request->all()
                ]);
            }
            
            // Vérifier si un utilisateur avec cet email existe déjà
            $existingUser = $userRepo->findOneBy(['email' => $email]);
            
            if ($existingUser) {
                $this->addFlash('error', 'Un utilisateur avec cet email existe déjà !');
                return $this->render('admin/add.html.twig', [
                    'formData' => $request->request->all()
                ]);
            }
            
            // Créer le nouvel utilisateur
            $user = new User();
            $user->setNom((string) $request->request->get('nom'));
            $user->setPrenom((string) $request->request->get('prenom'));
            $user->setEmail($email);
            $user->setTelephone($request->request->get('telephone'));
            $user->setSpecialite($request->request->get('specialite'));

            $role = (string) $request->request->get('role');
            if (!$role) { 
                $role = 'ROLE_USER'; 
            }
            $user->setRoles([$role]);

            $password = (string) $request->request->get('password');
            if (empty($password)) {
                $this->addFlash('error', 'Le mot de passe est obligatoire !');
                return $this->render('admin/add.html.twig', [
                    'formData' => $request->request->all()
                ]);
            }
            
            $user->setPassword($passwordHasher->hashPassword($user, $password));

            try {
                $em->persist($user);
                $em->flush();

                $this->addFlash('success', 'Utilisateur ajouté avec succès !');
                return $this->redirectToRoute('admin_dashboard');
            } catch (UniqueConstraintViolationException $e) {
                $this->addFlash('error', 'Erreur : cet email est déjà utilisé.');
                return $this->render('admin/add.html.twig', [
                    'formData' => $request->request->all()
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'ajout de l\'utilisateur : ' . $e->getMessage());
                return $this->render('admin/add.html.twig', [
                    'formData' => $request->request->all()
                ]);
            }
        }

        return $this->render('admin/add.html.twig');
    }

    #[Route('/admin/user/edit/{id}', name: 'admin_user_edit')]
    public function edit(User $user, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, UserRepository $userRepo): Response
    {
        if ($request->isMethod('POST')) {
            $email = trim((string) $request->request->get('email'));
            
            // Vérifier si l'email a changé et s'il est déjà utilisé par un autre utilisateur
            if ($email !== $user->getEmail()) {
                $existingUser = $userRepo->findOneBy(['email' => $email]);
                
                if ($existingUser && $existingUser->getId() !== $user->getId()) {
                    $this->addFlash('error', 'Un autre utilisateur utilise déjà cet email !');
                    return $this->render('admin/edit.html.twig', [
                        'user' => $user,
                        'formData' => $request->request->all()
                    ]);
                }
                
                $user->setEmail($email);
            }
            
            $user->setNom((string) $request->request->get('nom'));
            $user->setPrenom((string) $request->request->get('prenom'));
            $user->setTelephone($request->request->get('telephone'));
            $user->setSpecialite($request->request->get('specialite'));

            // Mise à jour du rôle si fourni
            $role = (string) $request->request->get('role');
            if ($role) {
                $user->setRoles([$role]);
            }

            // Mise à jour du mot de passe uniquement s'il est fourni
            $password = (string) $request->request->get('password');
            if (!empty($password)) {
                $user->setPassword($passwordHasher->hashPassword($user, $password));
            }

            try {
                $em->flush();
                $this->addFlash('success', 'Utilisateur modifié avec succès !');
                return $this->redirectToRoute('admin_dashboard');
            } catch (UniqueConstraintViolationException $e) {
                $this->addFlash('error', 'Erreur : cet email est déjà utilisé.');
                return $this->render('admin/edit.html.twig', [
                    'user' => $user,
                    'formData' => $request->request->all()
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la modification : ' . $e->getMessage());
                return $this->render('admin/edit.html.twig', [
                    'user' => $user,
                    'formData' => $request->request->all()
                ]);
            }
        }

        return $this->render('admin/edit.html.twig', ['user' => $user]);
    }

    #[Route('/admin/user/delete/{id}', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(User $user, Request $request, EntityManagerInterface $em): Response
    {
        // Vérification du token CSRF pour la sécurité
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            try {
                $em->remove($user);
                $em->flush();
                $this->addFlash('success', 'Utilisateur supprimé avec succès !');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression de l\'utilisateur : ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide !');
        }
        
        return $this->redirectToRoute('admin_dashboard');
    }

    // ==================== GESTION QUESTIONS ====================
    
    #[Route('/admin/question/new', name: 'app_question_new', methods: ['GET', 'POST'])]
    public function questionNew(Request $request, EntityManagerInterface $entityManager, QuestionRepository $questionRepo): Response
    {
        $question = new Question();
        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // *** Vérifier les doublons ***
                if ($questionRepo->existsByTexte($question->getTexte())) {
                    $this->addFlash('error', 'Une question avec ce texte existe déjà !');
                    return $this->render('admin/question/new.html.twig', [
                        'question' => $question,
                        'form' => $form,
                    ]);
                }
                
                $entityManager->persist($question);
                $entityManager->flush();
                $this->addFlash('success', 'Question créée avec succès !');
                return $this->redirectToRoute('admin_dashboard');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création de la question : ' . $e->getMessage());
            }
        }

        return $this->render('admin/question/new.html.twig', [
            'question' => $question,
            'form' => $form,
        ]);
    }

    #[Route('/admin/question/{id}', name: 'app_question_show', methods: ['GET'])]
    public function questionShow(Question $question): Response
    {
        return $this->render('admin/question/show.html.twig', [
            'question' => $question,
        ]);
    }

    #[Route('/admin/question/{id}/edit', name: 'app_question_edit', methods: ['GET', 'POST'])]
    public function questionEdit(Request $request, Question $question, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();
                $this->addFlash('success', 'Question modifiée avec succès !');
                return $this->redirectToRoute('admin_dashboard');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la modification de la question : ' . $e->getMessage());
            }
        }

        return $this->render('admin/question/edit.html.twig', [
            'question' => $question,
            'form' => $form,
        ]);
    }

    #[Route('/admin/question/{id}/delete', name: 'app_question_delete', methods: ['POST'])]
    public function questionDelete(Request $request, Question $question, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$question->getId(), $request->getPayload()->getString('_token'))) {
            try {
                $entityManager->remove($question);
                $entityManager->flush();
                $this->addFlash('success', 'Question supprimée avec succès !');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression de la question : ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide !');
        }

        return $this->redirectToRoute('admin_dashboard');
    }
}