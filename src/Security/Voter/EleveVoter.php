<?php

namespace App\Security\Voter;

use App\Entity\Eleve;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * INVARIANT 1 : un élève n'accède qu'à ses propres données.
 * INVARIANT 2 : cloisonnement par établissement pour le personnel (proviseur/enseignant).
 */
final class EleveVoter extends Voter
{
    public const VIEW = 'ELEVE_VIEW';

    protected function supports(string $attribute, $subject): bool
    {
        return self::VIEW === $attribute && $subject instanceof Eleve;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Eleve $eleve */
        $eleve = $subject;

        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        if (in_array('ROLE_PROVISEUR', $user->getRoles(), true)) {
            return $user->getEtablissement()?->getId() === $eleve->getClasse()?->getEtablissement()?->getId();
        }

        if (in_array('ROLE_ENSEIGNANT', $user->getRoles(), true)) {
            $enseignant = $user->getProfilEnseignant();

            return null !== $enseignant && null !== $eleve->getClasse() && $enseignant->enseigneDans($eleve->getClasse());
        }

        if (in_array('ROLE_ELEVE', $user->getRoles(), true)) {
            // INVARIANT 1 : comparaison stricte sur l'identité du compte, jamais sur la classe seule.
            return null !== $eleve->getUser() && $eleve->getUser()->getId() === $user->getId();
        }

        return false;
    }
}
