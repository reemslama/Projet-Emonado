<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

class SecurityController extends AbstractController
{
    #[Route('/admin/login', name: 'admin_login')]
    public function login(Request $request, SessionInterface $session): Response
    {
        // Si déjà connecté, rediriger vers l'admin
        if ($session->get('admin_authenticated')) {
            return $this->redirectToRoute('app_question_index');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $username = $request->request->get('username');
            $password = $request->request->get('password');

            // Identifiants en dur (vous pouvez les changer)
            $validUsername = 'admin';
            $validPassword = 'admin123';

            if ($username === $validUsername && $password === $validPassword) {
                $session->set('admin_authenticated', true);
                $session->set('admin_username', $username);
                
                return $this->redirectToRoute('app_question_index');
            } else {
                $error = 'Identifiants incorrects';
            }
        }

        return $this->render('admin/security/login.html.twig', [
            'error' => $error
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