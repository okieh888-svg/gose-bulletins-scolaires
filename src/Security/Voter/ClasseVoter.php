<?php

namespace App\Security\Voter;

use App\Entity\Classe;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Qui peut voir une classe ?
 * - ADMIN : toutes.
 * - PROVISEUR : uniquement les classes de SON établissement (cloisonnement).
 * - ENSEIGNANT : uniquement les classes où il a au moins une affectation.
 * - ELEVE : uniquement sa propre classe.
 */
final class ClasseVoter extends Voter
{
    public const VIEW = 'CLASSE_VIEW';

    protected function supports(string $attribute, $subject): bool
    {
        return self::VIEW === $attribute && $subject instanceof Classe;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Classe $classe */
        $classe = $subject;

        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        if (in_array('ROLE_PROVISEUR', $user->getRoles(), true)) {
            return $user->getEtablissement()?->getId() === $classe->getEtablissement()?->getId();
        }

        if (in_array('ROLE_ENSEIGNANT', $user->getRoles(), true)) {
            $enseignant = $user->getProfilEnseignant();

            return null !== $enseignant && $enseignant->enseigneDans($classe);
        }

        if (in_array('ROLE_ELEVE', $user->getRoles(), true)) {
            $eleve = $user->getProfilEleve();

            return null !== $eleve && $eleve->getClasse()?->getId() === $classe->getId();
        }

        return false;
    }
}
