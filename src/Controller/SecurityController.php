<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    private const RESET_TOKEN_VALIDITY = '+1 hour';

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/mot-de-passe-oublie', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(Request $request, UserRepository $userRepo, EntityManagerInterface $em, MailerInterface $mailer, UrlGeneratorInterface $urlGenerator): Response
    {
        $errors = [];
        $success = false;
        $linkSavedInDev = false;
        $email = trim((string) $request->request->get('email', ''));

        if ($request->isMethod('POST')) {
            if ($email === '') {
                $errors[] = 'Veuillez indiquer votre adresse email.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'L\'adresse email n\'est pas valide.';
            } else {
                $user = $userRepo->findOneBy(['email' => $email]);
                if (!$user) {
                    $success = true;
                } else {
                    $token = bin2hex(random_bytes(32));
                    $user->setResetPasswordToken($token);
                    $user->setResetPasswordTokenExpiresAt(new \DateTimeImmutable(self::RESET_TOKEN_VALIDITY));
                    $em->flush();

                    $resetUrl = $urlGenerator->generate('app_reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

                    $fromEmail = $this->getParameter('mailer_from') ?: 'reemslama21@gmail.com';
                    $emailMessage = (new Email())
                        ->from(new Address($fromEmail, 'Emonado'))
                        ->to($user->getEmail())
                        ->subject('Réinitialisation de votre mot de passe - Emonado')
                        ->text("Bonjour,\n\nPour réinitialiser votre mot de passe, cliquez sur le lien suivant (valide 1 heure) :\n\n" . $resetUrl . "\n\nSi vous n'êtes pas à l'origine de cette demande, ignorez cet email.\n\nL'équipe Emonado");

                    try {
                        $mailer->send($emailMessage);
                        $success = true;
                        // En dev : enregistrer le lien dans un fichier pour tester sans SMTP
                        if ($this->getParameter('kernel.environment') === 'dev') {
                            $projectDir = $this->getParameter('kernel.project_dir');
                            $mailDir = $projectDir . '/var/mail';
                            if (!is_dir($mailDir)) {
                                @mkdir($mailDir, 0775, true);
                            }
                            $linkFile = $projectDir . '/var/last_reset_link.txt';
                            file_put_contents($linkFile, $resetUrl . "\nGénéré le: " . (new \DateTimeImmutable())->format('Y-m-d H:i:s'));
                            $linkSavedInDev = true;
                        }
                    } catch (\Throwable $e) {
                        $errors[] = 'Impossible d\'envoyer l\'email. Veuillez réessayer plus tard.';
                        if ($this->getParameter('kernel.debug')) {
                            $errors[] = 'Détail: ' . $e->getMessage();
                        }
                    }
                }
            }
            if ($success) {
                $this->addFlash('success', 'Si ce compte existe, un email avec un lien pour réinitialiser votre mot de passe vous a été envoyé. Consultez votre boîte mail (et les spams).');
                if ($linkSavedInDev) {
                    $this->addFlash('info', 'Mode dev : le lien a aussi été enregistré dans var/last_reset_link.txt (ouvrez ce fichier pour copier le lien si vous ne recevez pas l\'email).');
                }
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/forgot_password.html.twig', [
            'errors' => $errors,
            'email' => $email,
        ]);
    }

    #[Route('/reinitialiser-mot-de-passe/{token}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(string $token, Request $request, UserRepository $userRepo, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $userRepo->findOneBy(['resetPasswordToken' => $token]);
        $errors = [];

        if (!$user || !$user->getResetPasswordTokenExpiresAt() || $user->getResetPasswordTokenExpiresAt() < new \DateTimeImmutable()) {
            $this->addFlash('error', 'Ce lien a expiré ou est invalide. Demandez une nouvelle réinitialisation.');
            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('reset_password', (string) $request->request->get('_csrf_token'))) {
                $errors[] = 'Session invalide. Veuillez réessayer.';
            }
            $password = (string) $request->request->get('password', '');
            $passwordConfirm = (string) $request->request->get('password_confirm', '');

            if ($password === '') {
                $errors[] = 'Le mot de passe est obligatoire.';
            } elseif (strlen($password) < 6) {
                $errors[] = 'Le mot de passe doit contenir au moins 6 caractères.';
            }
            if ($password !== $passwordConfirm) {
                $errors[] = 'Les deux mots de passe ne correspondent pas.';
            }

            if (empty($errors)) {
                $user->setPassword($passwordHasher->hashPassword($user, $password));
                $user->setResetPasswordToken(null);
                $user->setResetPasswordTokenExpiresAt(null);
                $em->flush();
                $this->addFlash('success', 'Votre mot de passe a été modifié. Vous pouvez vous connecter avec votre nouveau mot de passe.');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/reset_password.html.twig', [
            'token' => $token,
            'errors' => $errors,
        ]);
    }
}
