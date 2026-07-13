<?php

namespace App\Service;

use App\Entity\Bulletin;
use App\Entity\User;
use App\Enum\BulletinStatut;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Machine à états du bulletin : BROUILLON -> VALIDE_ENSEIGNANT -> PUBLIE.
 * Les transitions ne sont possibles que dans ce sens (pas de retour en arrière
 * depuis ce service ; une régénération repart de BulletinGenerator).
 *
 * IMPORTANT : ce service ne vérifie PAS les permissions (qui a le droit de
 * valider/publier) — c'est le rôle des Voters (App\Security\Voter\BulletinVoter),
 * appelés depuis le contrôleur AVANT d'invoquer ce service. Ce service garantit
 * uniquement la cohérence de la machine à états elle-même.
 */
class BulletinWorkflowService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly JournalisationService $journal,
    ) {
    }

    public function validerParEnseignant(Bulletin $bulletin, User $enseignant): void
    {
        if (BulletinStatut::BROUILLON !== $bulletin->getStatut()) {
            throw new \LogicException('Seul un bulletin en brouillon peut être validé par l\'enseignant.');
        }

        $bulletin->setStatut(BulletinStatut::VALIDE_ENSEIGNANT);
        $bulletin->setValideParEnseignant($enseignant);
        $bulletin->setDateValidationEnseignant(new \DateTimeImmutable());
        $this->em->flush();

        $this->journal->journaliser($enseignant, 'bulletin.valider_enseignant', 'Bulletin#'.$bulletin->getId());
    }

    public function publierParProviseur(Bulletin $bulletin, User $proviseur): void
    {
        if (BulletinStatut::VALIDE_ENSEIGNANT !== $bulletin->getStatut()) {
            throw new \LogicException('Seul un bulletin validé par l\'enseignant peut être publié.');
        }

        $bulletin->setStatut(BulletinStatut::PUBLIE);
        $bulletin->setPublieParProviseur($proviseur);
        $bulletin->setDatePublication(new \DateTimeImmutable());
        // Code de vérification anti-falsification, généré uniquement à la publication
        // (jamais en brouillon) — voir App\Controller\VerificationController.
        $bulletin->setCodeVerification(bin2hex(random_bytes(16)));
        $this->em->flush();

        $this->journal->journaliser($proviseur, 'bulletin.publier', 'Bulletin#'.$bulletin->getId());
    }

    public function peutEtreRegenere(Bulletin $bulletin): bool
    {
        // Un bulletin publié ne devrait pas être régénéré à la légère (archive figée).
        // Le prototype l'autorise pour l'enseignant/admin mais journalise l'opération ;
        // un passage en production interdirait probablement cette regénération sans
        // repasser par un motif documenté (hors périmètre de ce prototype).
        return true;
    }
}
