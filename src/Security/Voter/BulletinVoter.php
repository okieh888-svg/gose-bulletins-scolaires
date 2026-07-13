<?php

namespace App\Security\Voter;

use App\Entity\Bulletin;
use App\Entity\User;
use App\Enum\BulletinStatut;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Règles RBAC sur le bulletin — cœur du workflow de validation.
 *
 * - GENERATE : créer/régénérer un brouillon -> ENSEIGNANT (de la classe) ou ADMIN.
 * - VALIDATE : brouillon -> validé enseignant -> ENSEIGNANT (de la classe) ou ADMIN.
 * - PUBLISH  : validé enseignant -> publié -> PROVISEUR (de l'établissement) ou ADMIN.
 *              L'ENSEIGNANT ne peut JAMAIS publier, quelle que soit la classe.
 * - VIEW     : ADMIN (tout) / PROVISEUR (son établissement) / ENSEIGNANT (ses classes) /
 *              ELEVE (uniquement SON bulletin, et uniquement s'il est PUBLIÉ).
 */
final class BulletinVoter extends Voter
{
    public const VIEW = 'BULLETIN_VIEW';
    public const GENERATE = 'BULLETIN_GENERATE';
    public const VALIDATE = 'BULLETIN_VALIDATE';
    public const PUBLISH = 'BULLETIN_PUBLISH';

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::GENERATE, self::VALIDATE, self::PUBLISH], true)
            && $subject instanceof Bulletin;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Bulletin $bulletin */
        $bulletin = $subject;

        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        return match ($attribute) {
            self::VIEW => $this->peutVoir($bulletin, $user),
            self::GENERATE, self::VALIDATE => $this->estEnseignantDeLaClasse($bulletin, $user),
            self::PUBLISH => $this->estProviseurDeLEtablissement($bulletin, $user),
            default => false,
        };
    }

    private function peutVoir(Bulletin $bulletin, User $user): bool
    {
        if (in_array('ROLE_PROVISEUR', $user->getRoles(), true)) {
            return $this->memeEtablissement($bulletin, $user);
        }

        if (in_array('ROLE_ENSEIGNANT', $user->getRoles(), true)) {
            return $this->estEnseignantDeLaClasse($bulletin, $user);
        }

        if (in_array('ROLE_ELEVE', $user->getRoles(), true)) {
            // INVARIANT 1 (propriété des données) + règle métier : uniquement si publié.
            $estSonBulletin = null !== $bulletin->getEleve()->getUser()
                && $bulletin->getEleve()->getUser()->getId() === $user->getId();

            return $estSonBulletin && BulletinStatut::PUBLIE === $bulletin->getStatut();
        }

        return false;
    }

    private function estEnseignantDeLaClasse(Bulletin $bulletin, User $user): bool
    {
        if (!in_array('ROLE_ENSEIGNANT', $user->getRoles(), true)) {
            return false;
        }

        $enseignant = $user->getProfilEnseignant();
        $classe = $bulletin->getEleve()->getClasse();

        return null !== $enseignant && null !== $classe && $enseignant->enseigneDans($classe);
    }

    private function estProviseurDeLEtablissement(Bulletin $bulletin, User $user): bool
    {
        if (!in_array('ROLE_PROVISEUR', $user->getRoles(), true)) {
            return false; // exclut explicitement ROLE_ENSEIGNANT : il ne publie jamais.
        }

        return $this->memeEtablissement($bulletin, $user);
    }

    // INVARIANT 2 : cloisonnement par établissement.
    private function memeEtablissement(Bulletin $bulletin, User $user): bool
    {
        return $user->getEtablissement()?->getId()
            === $bulletin->getEleve()->getClasse()?->getEtablissement()?->getId();
    }
}
