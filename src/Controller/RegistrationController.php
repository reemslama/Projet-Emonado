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
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        $error = null;

        if ($request->isMethod('POST')) {
            $submittedToken = $request->request->get('_csrf_token');
            if (!$csrfTokenManager->isTokenValid(new CsrfToken('register', $submittedToken))) {
                throw $this->createAccessDeniedException('Invalid CSRF token');
            }

            $email = $request->request->get('email');
            $password = $request->request->get('password');
            $nom = $request->request->get('nom');
            $prenom = $request->request->get('prenom');
            $telephone = $request->request->get('telephone');
            $sexe = $request->request->get('sexe');
            $dateNaissance = $request->request->get('date_naissance');

            $existingUser = $entityManager->getRepository(User::class)
                ->findOneBy(['email' => $email]);

            if ($existingUser) {
                $error = 'Cet email est déjà utilisé.';
            } else {
                $user = new User();
                $user->setEmail($email);
                $user->setPassword($passwordHasher->hashPassword($user, $password));
                $user->setNom($nom);
                $user->setPrenom($prenom);
                $user->setTelephone($telephone);
                $user->setSexe($sexe);
                if ($dateNaissance) {
                    $user->setDateNaissance(new \DateTime($dateNaissance));
                }
                $user->setRoles(['ROLE_PATIENT']);

                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', 'Compte créé avec succès !');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('registration/register.html.twig', [
            'error' => $error,
        ]);
    }
}
