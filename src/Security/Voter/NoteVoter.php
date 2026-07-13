<?php

namespace App\Security\Voter;

use App\Entity\Affectation;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Qui peut saisir des notes pour une affectation (classe + matière) donnée ?
 * Seul l'enseignant titulaire de l'affectation (ou l'admin) peut saisir/modifier
 * des notes — ni le proviseur, ni un autre enseignant, ni a fortiori un élève.
 */
final class NoteVoter extends Voter
{
    public const SAISIR = 'NOTE_SAISIR';

    protected function supports(string $attribute, $subject): bool
    {
        return self::SAISIR === $attribute && $subject instanceof Affectation;
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

        if (!in_array('ROLE_ENSEIGNANT', $user->getRoles(), true)) {
            return false;
        }

        /** @var Affectation $affectation */
        $affectation = $subject;
        $enseignant = $user->getProfilEnseignant();

        return null !== $enseignant && $affectation->getEnseignant()?->getId() === $enseignant->getId();
    }
}
