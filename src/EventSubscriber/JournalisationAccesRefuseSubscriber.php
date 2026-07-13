<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\JournalisationService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;

/**
 * Journalise systématiquement toute tentative d'accès refusée (403), qu'elle
 * provienne de access_control (security.yaml) ou d'un Voter métier. Permet
 * de vérifier a posteriori qu'un élève qui tente d'accéder aux données d'un
 * autre élève (ou un enseignant qui tente de publier) est bien tracé.
 */
class JournalisationAccesRefuseSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly JournalisationService $journal,
        private readonly Security $security,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        // Priorité élevée : le listener de sécurité de Symfony (qui transforme
        // l'AccessDeniedException en réponse 403) arrête la propagation de
        // l'événement une fois passé. Sans cette priorité, ce subscriber ne
        // s'exécuterait jamais et aucun accès refusé ne serait journalisé.
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 10],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if (!$exception instanceof AccessDeniedException) {
            return;
        }

        $user = $this->security->getUser();

        $this->journal->journaliser(
            $user instanceof User ? $user : null,
            'acces_refuse',
            $event->getRequest()->getPathInfo()
        );
    }
}
