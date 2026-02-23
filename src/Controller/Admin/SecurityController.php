<?php

namespace App\Controller\Admin;

use App\Entity\AuditLog;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class SecurityController extends AbstractController
{
    #[Route('/admin/login', name: 'admin_login')]
    public function login(Request $request, SessionInterface $session, UserRepository $userRepo, UserPasswordHasherInterface $hasher, EntityManagerInterface $em): Response
    {
        if ($session->get('admin_authenticated')) {
            return $this->redirectToRoute('app_question_index');
        }

        $error = null;
        $lastUsername = '';

        if ($request->isMethod('POST')) {
            $username = trim((string) $request->request->get('username', ''));
            $password = $request->request->get('password', '');

            // Connexion par email (compte User avec ROLE_ADMIN)
            $user = $userRepo->findOneBy(['email' => $username]);
            if ($user && \in_array('ROLE_ADMIN', $user->getRoles(), true) && $hasher->isPasswordValid($user, $password)) {
                $session->set('admin_authenticated', true);
                $session->set('admin_username', $user->getUserIdentifier());
                $session->set('admin_user_id', $user->getId());

                $log = new AuditLog();
                $log->setAction('admin_login_success');
                $log->setEntityType('User');
                $log->setEntityId($user->getId());
                $log->setDetails('Connexion admin');
                $log->setUser($user);
                $log->setIp($request->getClientIp());
                $em->persist($log);
                $em->flush();

                return $this->redirectToRoute('app_question_index');
            }

            $error = 'Identifiants incorrects. Utilisez l\'email du compte admin (ex: admin@emonado.com) et son mot de passe.';
            $lastUsername = $username;
        }

        return $this->render('admin/security/login.html.twig', [
            'error' => $error,
            'last_username' => $lastUsername,
        ]);
    }

    #[Route('/admin/logout', name: 'admin_logout')]
    public function logout(SessionInterface $session): Response
    {
        $session->remove('admin_authenticated');
        $session->remove('admin_username');
        
        $this->addFlash('success', 'Vous avez été déconnecté avec succès');
        
        return $this->redirectToRoute('app_choix');
    }
}