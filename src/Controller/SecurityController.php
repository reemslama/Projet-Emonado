<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
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
    public function forgotPassword(
        Request $request,
        UserRepository $userRepo,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        UrlGeneratorInterface $urlGenerator,
        LoggerInterface $logger
    ): Response {
        $errors = [];
        $success = false;
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
                    $from = (string) $this->getParameter('mailer_from');
                    $fromName = (string) $this->getParameter('mailer_from_name');
                    $emailMessage = (new Email())
                        ->from(new Address($from, $fromName))
                        ->replyTo($from)
                        ->to($user->getEmail())
                        ->subject('Reinitialisation de votre mot de passe - Emonado')
                        ->text(
                            "Bonjour,\n\nPour reinitialiser votre mot de passe, cliquez sur le lien suivant (valide 1 heure):\n\n"
                            . $resetUrl
                            . "\n\nSi vous n'etes pas a l'origine de cette demande, ignorez cet email.\n\nL'equipe Emonado"
                        );

                    try {
                        $mailer->send($emailMessage);
                        $success = true;
                    } catch (\Throwable $e) {
                        $logger->error('Erreur envoi email reset password', ['exception' => $e]);
                        $errors[] = 'Impossible d\'envoyer l\'email. Veuillez reessayer plus tard.';
                    }
                }
            }

            if ($success) {
                $this->addFlash('success', 'Si ce compte existe, un email avec un lien de reinitialisation a ete envoye.');

                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/forgot_password.html.twig', [
            'errors' => $errors,
            'email' => $email,
        ]);
    }

    #[Route('/reinitialiser-mot-de-passe/{token}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(
        string $token,
        Request $request,
        UserRepository $userRepo,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = $userRepo->findOneBy(['resetPasswordToken' => $token]);
        $errors = [];

        if (!$user || !$user->getResetPasswordTokenExpiresAt() || $user->getResetPasswordTokenExpiresAt() < new \DateTimeImmutable()) {
            $this->addFlash('error', 'Ce lien a expire ou est invalide. Demandez une nouvelle reinitialisation.');

            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('reset_password', (string) $request->request->get('_csrf_token'))) {
                $errors[] = 'Session invalide. Veuillez reessayer.';
            }

            $password = (string) $request->request->get('password', '');
            $passwordConfirm = (string) $request->request->get('password_confirm', '');

            if ($password === '') {
                $errors[] = 'Le mot de passe est obligatoire.';
            } elseif (strlen($password) < 6) {
                $errors[] = 'Le mot de passe doit contenir au moins 6 caracteres.';
            }

            if ($password !== $passwordConfirm) {
                $errors[] = 'Les deux mots de passe ne correspondent pas.';
            }

            if (empty($errors)) {
                $user->setPassword($passwordHasher->hashPassword($user, $password));
                $user->setResetPasswordToken(null);
                $user->setResetPasswordTokenExpiresAt(null);
                $em->flush();

                $this->addFlash('success', 'Votre mot de passe a ete modifie. Vous pouvez vous connecter avec votre nouveau mot de passe.');

                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/reset_password.html.twig', [
            'token' => $token,
            'errors' => $errors,
        ]);
    }
}
