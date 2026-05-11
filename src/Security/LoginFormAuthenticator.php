<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(private UrlGeneratorInterface $urlGenerator) {}

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');
        $request->getSession()->set('_security.last_username', $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($request->request->get('password', '')),
            [
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?RedirectResponse
    {
        $user = $token->getUser();
        $roles = $user->getRoles();
        $requestedRole = (string) $request->request->get('role', '');

        if ($requestedRole === 'patient' && !in_array('ROLE_PATIENT', $roles, true)) {
            $request->getSession()->getFlashBag()->add('error', 'Ce compte n est pas un compte patient.');
            return new RedirectResponse($this->urlGenerator->generate('app_login'));
        }

        if ($requestedRole === 'psychologue' && !in_array('ROLE_PSYCHOLOGUE', $roles, true)) {
            $request->getSession()->getFlashBag()->add('error', 'Ce compte n est pas un compte psychologue.');
            return new RedirectResponse($this->urlGenerator->generate('app_login'));
        }

        if ($requestedRole === 'admin' && !in_array('ROLE_ADMIN', $roles, true)) {
            $request->getSession()->getFlashBag()->add('error', 'Ce compte n est pas un compte admin.');
            return new RedirectResponse($this->urlGenerator->generate('app_login'));
        }

        if (in_array('ROLE_PATIENT', $roles, true)) {
            return new RedirectResponse($this->urlGenerator->generate('patient_index'));
        }

        if (in_array('ROLE_PSYCHOLOGUE', $roles, true)) {
            return new RedirectResponse($this->urlGenerator->generate('psychologue_index'));
        }

        if (in_array('ROLE_ADMIN', $roles, true)) {
            return new RedirectResponse($this->urlGenerator->generate('admin_dashboard'));
        }

        return new RedirectResponse('/');
    }

    public function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
