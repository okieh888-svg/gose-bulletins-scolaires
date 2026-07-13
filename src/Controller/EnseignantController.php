<?php

namespace App\Controller;

use App\Entity\Classe;
use App\Entity\Matiere;
use App\Entity\Note;
use App\Entity\Periode;
use App\Entity\User;
use App\Repository\PeriodeRepository;
use App\Security\Voter\BulletinVoter;
use App\Security\Voter\ClasseVoter;
use App\Security\Voter\NoteVoter;
use App\Service\BulletinGenerator;
use App\Service\BulletinWorkflowService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/enseignant')]
class EnseignantController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PeriodeRepository $periodeRepository,
        private readonly BulletinGenerator $bulletinGenerator,
        private readonly BulletinWorkflowService $workflow,
    ) {
    }

    #[Route('', name: 'app_enseignant_index')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $enseignant = $user->getProfilEnseignant();

        if (null === $enseignant) {
            throw $this->createAccessDeniedException('Aucun profil enseignant associé à ce compte.');
        }

        return $this->render('enseignant/index.html.twig', [
            'classes' => $enseignant->getClasses(),
            'enseignant' => $enseignant,
        ]);
    }

    #[Route('/classe/{id}', name: 'app_enseignant_classe')]
    public function classe(Classe $classe, Request $request): Response
    {
        $this->denyAccessUnlessGranted(ClasseVoter::VIEW, $classe);

        /** @var User $user */
        $user = $this->getUser();
        $enseignant = $user->getProfilEnseignant();

        $periodes = $this->periodeRepository->findByEtablissementOrdonnees($classe->getEtablissement()->getId());
        $periodeSelectionneeId = $request->query->getInt('periode') ?: ($periodes[array_key_last($periodes)]?->getId() ?? null);
        $periodeSelectionnee = null;
        foreach ($periodes as $p) {
            if ($p->getId() === $periodeSelectionneeId) {
                $periodeSelectionnee = $p;
                break;
            }
        }

        // $enseignant est null pour un ROLE_ADMIN consultant cet écran (autorisé par
        // security.yaml) : dans ce cas on affiche toutes les affectations de la classe.
        $matieresEnseignees = [];
        foreach ($classe->getAffectations() as $affectation) {
            if (null === $enseignant || $affectation->getEnseignant()->getId() === $enseignant->getId()) {
                $matieresEnseignees[] = $affectation;
            }
        }

        $moyennesClasse = $periodeSelectionnee
            ? $this->bulletinGenerator->calculerMoyennesClasse($classe, $periodeSelectionnee)
            : [];

        $bulletinsExistants = [];
        if ($periodeSelectionnee) {
            foreach ($classe->getEleves() as $eleve) {
                $bulletin = $this->em->getRepository(\App\Entity\Bulletin::class)
                    ->findOneByEleveEtPeriode($eleve, $periodeSelectionnee);
                if ($bulletin) {
                    $bulletinsExistants[$eleve->getId()] = $bulletin;
                }
            }
        }

        return $this->render('enseignant/classe.html.twig', [
            'classe' => $classe,
            'periodes' => $periodes,
            'periodeSelectionnee' => $periodeSelectionnee,
            'affectations' => $matieresEnseignees,
            'moyennesClasse' => $moyennesClasse,
            'bulletinsExistants' => $bulletinsExistants,
        ]);
    }

    #[Route('/classe/{classeId}/matiere/{matiereId}/periode/{periodeId}/notes', name: 'app_enseignant_notes')]
    public function notes(
        int $classeId,
        int $matiereId,
        int $periodeId,
        Request $request,
    ): Response {
        $classe = $this->em->getRepository(Classe::class)->find($classeId) ?? throw $this->createNotFoundException();
        $matiere = $this->em->getRepository(Matiere::class)->find($matiereId) ?? throw $this->createNotFoundException();
        $periode = $this->em->getRepository(Periode::class)->find($periodeId) ?? throw $this->createNotFoundException();

        /** @var User $user */
        $user = $this->getUser();
        $enseignant = $user->getProfilEnseignant();

        // $enseignant est null pour un ROLE_ADMIN (voir commentaire équivalent dans classe()) :
        // on se contente alors de retrouver l'affectation par classe/matière, sans filtrer sur l'enseignant.
        $affectation = null;
        foreach ($classe->getAffectations() as $a) {
            if ($a->getMatiere()->getId() === $matiere->getId()
                && (null === $enseignant || $a->getEnseignant()->getId() === $enseignant->getId())
            ) {
                $affectation = $a;
                break;
            }
        }

        if (null === $affectation) {
            throw $this->createNotFoundException('Affectation introuvable.');
        }

        $this->denyAccessUnlessGranted(NoteVoter::SAISIR, $affectation);

        $noteRepository = $this->em->getRepository(Note::class);

        if ($request->isMethod('POST')) {
            $this->denyAccessUnlessGranted(NoteVoter::SAISIR, $affectation);

            $submittedToken = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('saisie_notes', $submittedToken)) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            $typeEvaluation = $request->request->get('type_evaluation', 'Devoir');
            $valeurs = $request->request->all('notes');

            foreach ($classe->getEleves() as $eleve) {
                $brute = $valeurs[$eleve->getId()] ?? null;
                if (null === $brute || '' === $brute) {
                    continue;
                }

                $note = new Note();
                $note->setEleve($eleve);
                $note->setMatiere($matiere);
                $note->setPeriode($periode);
                $note->setValeur((float) str_replace(',', '.', $brute));
                $note->setTypeEvaluation($typeEvaluation);
                $note->setSaisiePar($user);
                $this->em->persist($note);
            }
            $this->em->flush();

            $this->addFlash('success', 'Notes enregistrées.');

            return $this->redirectToRoute('app_enseignant_notes', [
                'classeId' => $classeId,
                'matiereId' => $matiereId,
                'periodeId' => $periodeId,
            ]);
        }

        $notesParEleve = [];
        foreach ($classe->getEleves() as $eleve) {
            $notesParEleve[$eleve->getId()] = $noteRepository->findPourEleveMatierePeriode($eleve, $matiere, $periode);
        }

        return $this->render('enseignant/notes.html.twig', [
            'classe' => $classe,
            'matiere' => $matiere,
            'periode' => $periode,
            'notesParEleve' => $notesParEleve,
        ]);
    }

    #[Route('/bulletin/{eleveId}/{periodeId}/generer', name: 'app_enseignant_bulletin_generer', methods: ['POST'])]
    public function genererBulletin(int $eleveId, int $periodeId, Request $request): Response
    {
        $eleve = $this->em->getRepository(\App\Entity\Eleve::class)->find($eleveId) ?? throw $this->createNotFoundException();
        $periode = $this->em->getRepository(Periode::class)->find($periodeId) ?? throw $this->createNotFoundException();

        if (!$this->isCsrfTokenValid('generer_bulletin_'.$eleveId, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        // Vérifie le droit via un bulletin transitoire (pas encore persisté) pour la classe/matière de l'élève.
        $bulletinTransitoire = new \App\Entity\Bulletin();
        $bulletinTransitoire->setEleve($eleve);
        $bulletinTransitoire->setPeriode($periode);
        $this->denyAccessUnlessGranted(BulletinVoter::GENERATE, $bulletinTransitoire);

        /** @var User $user */
        $user = $this->getUser();
        $bulletin = $this->bulletinGenerator->genererPourEleve($eleve, $periode, $user);

        $this->addFlash('success', sprintf('Bulletin de %s généré (brouillon).', $eleve->getNomComplet()));

        return $this->redirectToRoute('app_enseignant_classe', ['id' => $eleve->getClasse()->getId(), 'periode' => $periodeId]);
    }

    #[Route('/bulletin/{id}/valider', name: 'app_enseignant_bulletin_valider', methods: ['POST'])]
    public function validerBulletin(int $id, Request $request): Response
    {
        $bulletin = $this->em->getRepository(\App\Entity\Bulletin::class)->find($id) ?? throw $this->createNotFoundException();
        $this->denyAccessUnlessGranted(BulletinVoter::VALIDATE, $bulletin);

        if (!$this->isCsrfTokenValid('valider_bulletin_'.$id, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        /** @var User $user */
        $user = $this->getUser();
        $this->workflow->validerParEnseignant($bulletin, $user);

        $this->addFlash('success', 'Bulletin validé et transmis au proviseur pour publication.');

        return $this->redirectToRoute('app_enseignant_classe', [
            'id' => $bulletin->getEleve()->getClasse()->getId(),
            'periode' => $bulletin->getPeriode()->getId(),
        ]);
    }
}
