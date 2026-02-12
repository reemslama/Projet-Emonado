<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class RegistrationController extends AbstractController
{
    private const PASSWORD_MIN_LENGTH = 6;

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        $errors = [];
        $formData = [
            'nom' => '',
            'prenom' => '',
            'email' => '',
            'telephone' => '',
            'sexe' => '',
            'date_naissance' => '',
        ];

        if ($request->isMethod('POST')) {
            $submittedToken = $request->request->get('_csrf_token');
            if (!$csrfTokenManager->isTokenValid(new CsrfToken('register', $submittedToken))) {
                $errors[] = 'Session invalide. Veuillez réessayer.';
            } else {
                $nom = trim((string) $request->request->get('nom', ''));
                $prenom = trim((string) $request->request->get('prenom', ''));
                $email = trim((string) $request->request->get('email', ''));
                $password = (string) $request->request->get('password', '');
                $telephone = trim((string) $request->request->get('telephone', ''));
                $sexe = trim((string) $request->request->get('sexe', ''));
                $dateNaissance = trim((string) $request->request->get('date_naissance', ''));

                $formData = [
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'email' => $email,
                    'telephone' => $telephone,
                    'sexe' => $sexe,
                    'date_naissance' => $dateNaissance,
                ];

                if ($nom === '') {
                    $errors[] = 'Le nom est obligatoire.';
                }
                if ($prenom === '') {
                    $errors[] = 'Le prénom est obligatoire.';
                }
                if ($email === '') {
                    $errors[] = 'L\'email est obligatoire.';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'L\'email n\'est pas valide.';
                }
                if ($password === '') {
                    $errors[] = 'Le mot de passe est obligatoire.';
                } elseif (strlen($password) < self::PASSWORD_MIN_LENGTH) {
                    $errors[] = 'Le mot de passe doit contenir au moins ' . self::PASSWORD_MIN_LENGTH . ' caractères.';
                }

                if (empty($errors)) {
                    $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
                    if ($existingUser) {
                        $errors[] = 'Cet email est déjà utilisé.';
                    }
                }

                if (empty($errors)) {
                    $user = new User();
                    $user->setEmail($email);
                    $user->setPassword($passwordHasher->hashPassword($user, $password));
                    $user->setNom($nom);
                    $user->setPrenom($prenom);
                    $user->setTelephone($telephone ?: null);
                    $user->setSexe($sexe ?: null);
                    if ($dateNaissance !== '') {
                        try {
                            $user->setDateNaissance(new \DateTime($dateNaissance));
                        } catch (\Exception $e) {
                            $errors[] = 'La date de naissance n\'est pas valide.';
                        }
                    }
                    $user->setRoles(['ROLE_PATIENT']);

                    if (empty($errors)) {
                        $entityManager->persist($user);
                        $entityManager->flush();
                        $this->addFlash('success', 'Compte créé avec succès.');
                        return $this->redirectToRoute('app_login');
                    }
                }
            }
        }

        return $this->render('registration/register.html.twig', [
            'errors' => $errors,
            'formData' => $formData,
        ]);
    }
}
