<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PsychologueController extends AbstractController
{
    #[Route('/psychologue', name: 'psychologue_index')]
    public function index(): Response
    {
        return $this->render('psychologue/index.html.twig');
    }

    #[Route('/psychologue/profil', name: 'psychologue_profil')]
    public function profil(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $user->setNom($request->request->get('nom'));
            $user->setPrenom($request->request->get('prenom'));
            $user->setTelephone($request->request->get('telephone'));
            $user->setSpecialite($request->request->get('specialite'));

            $password = $request->request->get('password');
            if ($password) {
                $user->setPassword($passwordHasher->hashPassword($user, $password));
            }

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Profil mis à jour avec succès !');
            return $this->redirectToRoute('psychologue_profil');
        }

        return $this->render('profil_psychologue/index.html.twig', [
            'user' => $user,
            'error' => null, // <-- corrige l'erreur
        ]);
    }

    #[Route('/psychologue/profil/delete', name: 'psychologue_profil_delete', methods: ['POST'])]
    public function delete(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if ($user) {
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'Votre profil a été supprimé avec succès !');
        }
        return $this->redirectToRoute('app_login');
    }
}
