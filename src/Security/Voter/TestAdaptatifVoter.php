<?php

namespace App\Security\Voter;

use App\Entity\TestAdaptatif;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TestAdaptatifVoter extends Voter
{
    public const VIEW = 'view';
    public const EDIT = 'edit';
    public const DELETE = 'delete';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Le voter gère seulement les actions sur TestAdaptatif
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])
            && $subject instanceof TestAdaptatif;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // Si l'utilisateur n'est pas connecté, refuser l'accès
        if (!$user instanceof User) {
            return false;
        }

        /** @var TestAdaptatif $test */
        $test = $subject;

        return match ($attribute) {
            self::VIEW => $this->canView($test, $user),
            self::EDIT => $this->canEdit($test, $user),
            self::DELETE => $this->canDelete($test, $user),
            default => false,
        };
    }

    private function canView(TestAdaptatif $test, User $user): bool
    {
        // Un admin peut tout voir
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Un psychologue peut voir les tests de ses patients
        if (in_array('ROLE_PSYCHOLOGUE', $user->getRoles())) {
            return true; // À affiner selon vos besoins (vérifier la relation psychologue-patient)
        }

        // Un patient peut voir uniquement ses propres tests
        if (in_array('ROLE_PATIENT', $user->getRoles())) {
            return $test->getPatient() === $user;
        }

        return false;
    }

    private function canEdit(TestAdaptatif $test, User $user): bool
    {
        // Seuls les admins et psychologues peuvent éditer
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        if (in_array('ROLE_PSYCHOLOGUE', $user->getRoles())) {
            return true;
        }

        // Un patient peut éditer son test seulement s'il n'est pas terminé
        if (in_array('ROLE_PATIENT', $user->getRoles())) {
            return $test->getPatient() === $user && !$test->isTermine();
        }

        return false;
    }

    private function canDelete(TestAdaptatif $test, User $user): bool
    {
        // Seuls les admins peuvent supprimer
        return in_array('ROLE_ADMIN', $user->getRoles());
    }
}
