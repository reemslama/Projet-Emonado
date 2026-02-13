<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProfilPsychologueController extends AbstractController
{
    #[Route('/psychologue/profil', name: 'psychologue_profil')]
    public function profil(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $error = null;

        // Mise à jour du profil
        if ($request->isMethod('POST') && $request->request->get('action') === 'update') {
            $user->setNom($request->request->get('nom'));
            $user->setPrenom($request->request->get('prenom'));
            $user->setTelephone($request->request->get('telephone'));

            $password = $request->request->get('password');
            if ($password) {
                $user->setPassword($passwordHasher->hashPassword($user, $password));
            }

            $specialite = $request->request->get('specialite');
            if ($specialite) {
                $user->setSpecialite($specialite);
            }

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Profil mis à jour avec succès !');
            return $this->redirectToRoute('psychologue_profil');
        }

        // Supprimer le profil
        if ($request->isMethod('POST') && $request->request->get('action') === 'delete') {
            $em->remove($user);
            $em->flush();

            $this->addFlash('success', 'Profil supprimé avec succès !');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('profil_psychologue/index.html.twig', [
            'user' => $user,
            'error' => $error,
        ]);
    }
}
