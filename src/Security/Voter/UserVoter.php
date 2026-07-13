<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Gestion des comptes utilisateurs (écran "Comptes" du proviseur).
 * Le proviseur gère UNIQUEMENT les comptes de son propre établissement, et ne
 * peut ni voir ni modifier un compte ROLE_ADMIN (cloisonnement + moindre privilège).
 */
final class UserVoter extends Voter
{
    public const MANAGE = 'USER_MANAGE';

    protected function supports(string $attribute, $subject): bool
    {
        return self::MANAGE === $attribute && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        if (!in_array('ROLE_PROVISEUR', $user->getRoles(), true)) {
            return false;
        }

        /** @var User $cible */
        $cible = $subject;

        if (in_array('ROLE_ADMIN', $cible->getRoles(), true)) {
            return false;
        }

        return $user->getEtablissement()?->getId() === $cible->getEtablissement()?->getId();
    }
}
