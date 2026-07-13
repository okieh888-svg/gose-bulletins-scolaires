<?php

namespace App\Service;

use App\Entity\JournalAcces;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/** Écrit les entrées du journal d'accès (qui / quoi / quand / depuis où). */
class JournalisationService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function journaliser(?User $utilisateur, string $action, ?string $cible = null): void
    {
        $entree = new JournalAcces();
        $entree->setUtilisateur($utilisateur);
        $entree->setAction($action);
        $entree->setCible($cible);
        $entree->setAdresseIp($this->requestStack->getCurrentRequest()?->getClientIp());

        $this->em->persist($entree);
        $this->em->flush();
    }
}
