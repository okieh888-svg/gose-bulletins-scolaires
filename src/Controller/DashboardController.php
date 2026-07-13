<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    /** Point d'entrée unique après connexion : redirige selon le rôle métier. */
    #[Route('/', name: 'app_dashboard')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return match (true) {
            $this->isGranted('ROLE_ADMIN') => $this->redirectToRoute('app_admin_index'),
            $this->isGranted('ROLE_PROVISEUR') => $this->redirectToRoute('app_proviseur_index'),
            $this->isGranted('ROLE_ENSEIGNANT') => $this->redirectToRoute('app_enseignant_index'),
            $this->isGranted('ROLE_ELEVE') => $this->redirectToRoute('app_eleve_index'),
            default => throw $this->createAccessDeniedException('Aucun rôle applicatif associé à ce compte.'),
        };
    }
}
