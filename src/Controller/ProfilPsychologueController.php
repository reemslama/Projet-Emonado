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
        if (!$user instanceof \App\Entity\User) {
            return $this->redirectToRoute('app_login');
        }

        $error = null;

        // Mise à jour du profil
        if ($request->isMethod('POST') && $request->request->get('action') === 'update') {
            $user->setNom((string) $request->request->get('nom'));
            $user->setPrenom((string) $request->request->get('prenom'));
            $tel = $request->request->get('telephone');
            $user->setTelephone(is_string($tel) && $tel !== '' ? $tel : null);

            $password = $request->request->get('password');
            if (is_string($password) && $password !== '') {
                $user->setPassword($passwordHasher->hashPassword($user, $password));
            }

            $specialite = $request->request->get('specialite');
            if (is_string($specialite) && $specialite !== '') {
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

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('Cette méthode peut rester vide.');
    }
}
