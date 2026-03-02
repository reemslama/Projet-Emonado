<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProfilController extends AbstractController
{
    #[Route('/patient/profil', name: 'patient_profil')]
    public function profil(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return $this->redirectToRoute('app_login');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $user->setNom((string) $request->request->get('nom'));
            $user->setPrenom((string) $request->request->get('prenom'));
            $tel = $request->request->get('telephone');
            $user->setTelephone(is_string($tel) && $tel !== '' ? $tel : null);
            $sexe = $request->request->get('sexe');
            $user->setSexe(is_string($sexe) && $sexe !== '' ? $sexe : null);

            $dateNaissance = $request->request->get('date_naissance');
            if (is_string($dateNaissance) && $dateNaissance !== '') {
                $user->setDateNaissance(new \DateTime($dateNaissance));
            }

            $password = $request->request->get('password');
            if (is_string($password) && $password !== '') {
                $user->setPassword($passwordHasher->hashPassword($user, $password));
            }

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Profil mis à jour avec succès !');
            return $this->redirectToRoute('patient_profil');
        }

        return $this->render('profil/index.html.twig', [
            'user' => $user,
            'error' => $error,
        ]);
    }
}
