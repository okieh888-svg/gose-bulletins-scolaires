<?php

namespace App\Service;

use App\Entity\Bulletin;
use App\Entity\BulletinLigne;
use App\Entity\Classe;
use App\Entity\Eleve;
use App\Entity\Periode;
use App\Entity\User;
use App\Enum\BulletinStatut;
use App\Repository\BulletinRepository;
use App\Repository\NoteRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Orchestre le calcul des moyennes/rang/appréciations et la persistance du
 * bulletin (brouillon). Ne gère PAS le workflow de validation/publication,
 * qui est délégué à BulletinWorkflowService.
 */
class BulletinGenerator
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly NoteRepository $noteRepository,
        private readonly BulletinRepository $bulletinRepository,
        private readonly MoyenneCalculator $moyenneCalculator,
        private readonly RangCalculator $rangCalculator,
        private readonly AppreciationService $appreciationService,
    ) {
    }

    /**
     * Calcule (sans persister) les moyennes de tous les élèves d'une classe pour
     * une période donnée. Nécessaire pour établir le rang de chacun.
     *
     * @return array<int, array{eleve: Eleve, moyenneGenerale: ?float, lignes: array<int, array{matiere: \App\Entity\Matiere, moyenne: float}>}>
     */
    public function calculerMoyennesClasse(Classe $classe, Periode $periode): array
    {
        $matieresParClasse = [];
        foreach ($classe->getAffectations() as $affectation) {
            $matieresParClasse[$affectation->getMatiere()->getId()] = $affectation->getMatiere();
        }

        $resultats = [];

        foreach ($classe->getEleves() as $eleve) {
            $lignes = [];
            $moyennesPourGenerale = [];

            foreach ($matieresParClasse as $matiere) {
                $notes = $this->noteRepository->findPourEleveMatierePeriode($eleve, $matiere, $periode);
                $valeurs = array_map(static fn ($n) => $n->getValeur(), $notes);
                $moyenneMatiere = $this->moyenneCalculator->moyenneMatiere($valeurs);

                if (null === $moyenneMatiere) {
                    continue; // pas de note saisie pour cette matière sur cette période
                }

                $lignes[] = ['matiere' => $matiere, 'moyenne' => $moyenneMatiere];
                $moyennesPourGenerale[] = ['moyenne' => $moyenneMatiere, 'coefficient' => $matiere->getCoefficient()];
            }

            $resultats[$eleve->getId()] = [
                'eleve' => $eleve,
                'moyenneGenerale' => $this->moyenneCalculator->moyenneGenerale($moyennesPourGenerale),
                'lignes' => $lignes,
            ];
        }

        return $resultats;
    }

    /**
     * Génère (ou régénère) le bulletin brouillon d'un élève pour une période.
     * Si un bulletin existe déjà et n'est plus au statut BROUILLON, il n'est PAS écrasé
     * silencieusement : voir BulletinWorkflowService::peutRegenerer().
     */
    public function genererPourEleve(Eleve $eleve, Periode $periode, User $genereParEnseignant): Bulletin
    {
        $classe = $eleve->getClasse();
        $moyennesClasse = $this->calculerMoyennesClasse($classe, $periode);

        $moyennesPourRang = [];
        foreach ($moyennesClasse as $eleveId => $donnees) {
            if (null !== $donnees['moyenneGenerale']) {
                $moyennesPourRang[$eleveId] = $donnees['moyenneGenerale'];
            }
        }
        $rangs = $this->rangCalculator->calculerRangs($moyennesPourRang);

        $donneesEleve = $moyennesClasse[$eleve->getId()];

        $bulletin = $this->bulletinRepository->findOneByEleveEtPeriode($eleve, $periode);
        if (null === $bulletin) {
            $bulletin = new Bulletin();
            $bulletin->setEleve($eleve);
            $bulletin->setPeriode($periode);
        } else {
            $bulletin->viderLignes();
            $this->em->flush(); // nécessaire pour que orphanRemoval supprime bien les anciennes lignes
        }

        $moyenneGenerale = $donneesEleve['moyenneGenerale'];
        $bulletin->setMoyenneGenerale($moyenneGenerale);
        $bulletin->setRang($rangs[$eleve->getId()] ?? null);
        $bulletin->setEffectifClasse(count($moyennesPourRang));
        $bulletin->setAppreciationGenerale(null !== $moyenneGenerale ? $this->appreciationService->appreciation($moyenneGenerale) : null);
        $bulletin->setStatut(BulletinStatut::BROUILLON);
        $bulletin->setDateGeneration(new \DateTimeImmutable());
        $bulletin->setGenereParEnseignant($genereParEnseignant);
        // Une régénération invalide une éventuelle validation/publication précédente.
        $bulletin->setValideParEnseignant(null);
        $bulletin->setDateValidationEnseignant(null);
        $bulletin->setPublieParProviseur(null);
        $bulletin->setDatePublication(null);
        // Un code de vérification n'est valable que pour le contenu publié exact
        // qu'il désigne : toute régénération l'invalide, une republication en génère un nouveau.
        $bulletin->setCodeVerification(null);

        foreach ($donneesEleve['lignes'] as $ligneDonnees) {
            $ligne = new BulletinLigne();
            $ligne->setMatiere($ligneDonnees['matiere']);
            $ligne->setMoyenne($ligneDonnees['moyenne']);
            $ligne->setCoefficient($ligneDonnees['matiere']->getCoefficient());
            $ligne->setMoyenneCoefficientee(round($ligneDonnees['moyenne'] * $ligneDonnees['matiere']->getCoefficient(), 2));
            $ligne->setAppreciation($this->appreciationService->appreciation($ligneDonnees['moyenne']));
            $bulletin->addLigne($ligne);
        }

        $this->em->persist($bulletin);
        $this->em->flush();

        return $bulletin;
    }

    /** @return Bulletin[] */
    public function genererPourClasse(Classe $classe, Periode $periode, User $genereParEnseignant): array
    {
        $bulletins = [];
        foreach ($classe->getEleves() as $eleve) {
            $bulletins[] = $this->genererPourEleve($eleve, $periode, $genereParEnseignant);
        }

        return $bulletins;
    }
}
