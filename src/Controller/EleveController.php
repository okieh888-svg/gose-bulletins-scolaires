<?php

namespace App\Controller;

use App\Entity\Periode;
use App\Entity\User;
use App\Repository\BulletinRepository;
use App\Repository\NoteRepository;
use App\Repository\PeriodeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Espace élève : lecture seule, strictement limité à SES propres bulletins publiés
 * (INVARIANT 1, appliqué ici en filtrant par son propre profil Eleve, et
 * revérifié par BulletinVoter::VIEW sur chaque bulletin individuel).
 */
#[Route('/eleve')]
class EleveController extends AbstractController
{
    public function __construct(
        private readonly BulletinRepository $bulletinRepository,
        private readonly NoteRepository $noteRepository,
        private readonly PeriodeRepository $periodeRepository,
    ) {
    }

    #[Route('', name: 'app_eleve_index')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $eleve = $user->getProfilEleve();

        if (null === $eleve) {
            throw $this->createAccessDeniedException('Aucun profil élève associé à ce compte.');
        }

        $bulletins = $this->bulletinRepository->findPubliesParEleve($eleve);

        return $this->render('eleve/index.html.twig', [
            'eleve' => $eleve,
            'bulletins' => $bulletins,
        ]);
    }

    #[Route('/notes', name: 'app_eleve_notes')]
    public function notes(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $eleve = $user->getProfilEleve();

        if (null === $eleve) {
            throw $this->createAccessDeniedException('Aucun profil élève associé à ce compte.');
        }

        $periodes = $this->periodeRepository->findByEtablissementOrdonnees($eleve->getClasse()->getEtablissement()->getId());
        $periodeId = $request->query->getInt('periode') ?: ($periodes[array_key_last($periodes)]?->getId() ?? null);
        $periodeSelectionnee = null;
        foreach ($periodes as $p) {
            if ($p->getId() === $periodeId) {
                $periodeSelectionnee = $p;
                break;
            }
        }

        $notes = $periodeSelectionnee instanceof Periode
            ? $this->noteRepository->findPourElevePeriode($eleve, $periodeSelectionnee)
            : [];

        return $this->render('eleve/notes.html.twig', [
            'eleve' => $eleve,
            'periodes' => $periodes,
            'periodeSelectionnee' => $periodeSelectionnee,
            'notes' => $notes,
        ]);
    }
}
