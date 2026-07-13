<?php

namespace App\DataFixtures;

use App\Entity\Affectation;
use App\Entity\Classe;
use App\Entity\Eleve;
use App\Entity\Enseignant;
use App\Entity\Etablissement;
use App\Entity\Matiere;
use App\Entity\Note;
use App\Entity\Periode;
use App\Entity\User;
use App\Service\BulletinGenerator;
use App\Service\BulletinWorkflowService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Jeu de données 100% FICTIF pour la démonstration GOSE.
 *
 * Aucune donnée réelle d'élève (mineur) n'est utilisée : tous les noms,
 * matricules et notes sont générés pour ce prototype uniquement.
 *
 * Volontairement inclus :
 * - 2 établissements (Lycée de Balbala / Collège d'Arta) pour DÉMONTRER le
 *   cloisonnement inter-établissements (voir tests/Security).
 * - Plusieurs élèves avec un nom en écriture arabe, pour prouver le support
 *   UTF-8 / RTL dès ce prototype (préparation du Lot 8 « arabisation »).
 * - Un mot de passe unique "Demo123!" pour tous les comptes de démonstration
 *   (documenté dans le README), clairement non destiné à un usage réel.
 */
class AppFixtures extends Fixture
{
    private const MOT_DE_PASSE_DEMO = 'Demo123!';

    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
        private readonly BulletinGenerator $bulletinGenerator,
        private readonly BulletinWorkflowService $workflow,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        mt_srand(42); // notes reproductibles d'un chargement de fixtures à l'autre

        // --- Établissements -------------------------------------------------
        $balbala = $this->creerEtablissement($manager, 'Lycée de Balbala', 'LYB01', 'Djibouti-ville (Balbala)');
        $arta = $this->creerEtablissement($manager, "Collège d'Arta", 'COL02', 'Arta');

        // --- Compte admin (aucun établissement de rattachement) -------------
        $admin = $this->creerUser($manager, 'admin@gose.dj', 'GOSE', 'Administrateur', ['ROLE_ADMIN'], null);

        // --- Proviseurs -------------------------------------------------------
        $proviseurBalbala = $this->creerUser($manager, 'proviseur.balbala@gose.dj', 'Waberi', 'Guelleh Ahmed', ['ROLE_PROVISEUR'], $balbala);
        $proviseurArta = $this->creerUser($manager, 'proviseur.arta@gose.dj', 'Farah', 'Roda Ismail', ['ROLE_PROVISEUR'], $arta);

        // --- Matières (communes aux deux établissements) ---------------------
        $matieres = [
            'francais' => $this->creerMatiere($manager, 'Français', 'FR', 4, null),
            'maths' => $this->creerMatiere($manager, 'Mathématiques', 'MATH', 4, null),
            'svt' => $this->creerMatiere($manager, 'Sciences de la Vie et de la Terre', 'SVT', 2, null),
            'pc' => $this->creerMatiere($manager, 'Physique-Chimie', 'PC', 2, null),
            'hg' => $this->creerMatiere($manager, 'Histoire-Géographie', 'HG', 2, null),
            'anglais' => $this->creerMatiere($manager, 'Anglais', 'ANG', 2, null),
            'arabe' => $this->creerMatiere($manager, 'Arabe', 'AR', 3, 'اللغة العربية'),
            'islamique' => $this->creerMatiere($manager, 'Éducation Islamique', 'EI', 1, 'التربية الإسلامية'),
        ];

        // --- Classes établissement 1 ------------------------------------------
        $classe6A = $this->creerClasse($manager, $balbala, '6ème A', '6ème');
        $classe6B = $this->creerClasse($manager, $balbala, '6ème B', '6ème');

        // --- Enseignants établissement 1 --------------------------------------
        $ens1 = $this->creerEnseignant($manager, 'ens.mahamoud@gose.dj', 'Warsame', 'Mahamoud Ali', $balbala);
        $ens2 = $this->creerEnseignant($manager, 'ens.amina@gose.dj', 'Guedi', 'Amina Robleh', $balbala);
        $ens3 = $this->creerEnseignant($manager, 'ens.omar@gose.dj', 'Farah', 'Omar Houmed', $balbala);
        $ens4 = $this->creerEnseignant($manager, 'ens.fatouma@gose.dj', 'Daoud', 'Fatouma Kamil', $balbala);

        // --- Affectations (répartition des 8 matières sur les 2 classes) -----
        foreach ([$classe6A, $classe6B] as $classe) {
            $this->creerAffectation($manager, $ens1, $matieres['francais'], $classe);
            $this->creerAffectation($manager, $ens2, $matieres['maths'], $classe);
            $this->creerAffectation($manager, $ens3, $matieres['svt'], $classe);
            $this->creerAffectation($manager, $ens3, $matieres['pc'], $classe);
            $this->creerAffectation($manager, $ens4, $matieres['hg'], $classe);
            $this->creerAffectation($manager, $ens4, $matieres['anglais'], $classe);
            $this->creerAffectation($manager, $ens2, $matieres['arabe'], $classe);
            $this->creerAffectation($manager, $ens1, $matieres['islamique'], $classe);
        }

        // --- Élèves établissement 1 -------------------------------------------
        $elevesData6A = [
            ['Waberi', 'Hibo Ahmed', 'F', '2013-04-12', null, 'eleve.hibo@gose.dj'],
            ['Robleh', 'Ismail Farah', 'M', '2013-06-23', null, 'eleve.ismail@gose.dj'],
            ['Guelleh', 'Amina Osman', 'F', '2013-01-30', null, 'eleve.amina@gose.dj'],
            ['Kamil', 'Mohamed Abdi', 'M', '2013-09-05', null, 'eleve.mohamed@gose.dj'],
            ['Elmi', 'Sagal Nour', 'F', '2013-11-18', null, 'eleve.sagal@gose.dj'],
            ['Ali', 'Yasin Cheikh', 'M', '2013-03-02', 'ياسين الشيخ علي', 'eleve.yasin@gose.dj'],
            ['Houmed', 'Zahra Ibrahim', 'F', '2013-07-14', 'زهراء إبراهيم حومد', 'eleve.zahra@gose.dj'],
            ['Daoud', 'Abdillahi Robleh', 'M', '2013-12-27', null, 'eleve.abdillahi@gose.dj'],
        ];
        $elevesData6B = [
            ['Dabar', 'Fatouma Ali', 'F', '2013-05-09', null, 'eleve.fatouma@gose.dj'],
            ['Guedi', 'Kadar Moussa', 'M', '2013-02-16', null, 'eleve.kadar@gose.dj'],
            ['Farah', 'Ayan Hassan', 'F', '2013-08-21', null, 'eleve.ayan@gose.dj'],
            ['Boureh', 'Omar Said', 'M', '2013-10-03', null, 'eleve.omar@gose.dj'],
            ['Warsame', 'Nimo Abdillahi', 'F', '2013-06-11', null, 'eleve.nimo@gose.dj'],
            ['Souleiman', 'Khadija Ahmed', 'F', '2013-04-25', 'خديجة أحمد سليمان', 'eleve.khadija@gose.dj'],
            ['Elmi', 'Bilal Youssouf', 'M', '2013-09-30', 'بلال يوسف علمي', 'eleve.bilal@gose.dj'],
        ];

        $tousLesEleves = [];
        $matricule = 1000;
        foreach ($elevesData6A as $donnees) {
            $tousLesEleves[] = $this->creerEleve($manager, $classe6A, ++$matricule, ...$donnees);
        }
        foreach ($elevesData6B as $donnees) {
            $tousLesEleves[] = $this->creerEleve($manager, $classe6B, ++$matricule, ...$donnees);
        }

        // --- Établissement 2 (uniquement pour démontrer le cloisonnement) ----
        $classeArta = $this->creerClasse($manager, $arta, '1ère A', '1ère');
        $ensArta = $this->creerEnseignant($manager, 'ens.arta@gose.dj', 'Ibrahim', 'Aden Robleh', $arta);
        $this->creerAffectation($manager, $ensArta, $matieres['francais'], $classeArta);
        $this->creerAffectation($manager, $ensArta, $matieres['maths'], $classeArta);

        $elevesArta = [
            ['Aden', 'Samira Robleh', 'F', '2010-02-14', null, 'eleve.samira@gose.dj'],
            ['Guelleh', 'Hassan Omar', 'M', '2010-05-19', null, 'eleve.hassan@gose.dj'],
            ['Ibrahim', 'Nasra Cheikh', 'F', '2010-08-08', 'نصرة الشيخ إبراهيم', 'eleve.nasra@gose.dj'],
        ];
        $elevesArtaEntites = [];
        foreach ($elevesArta as $donnees) {
            $elevesArtaEntites[] = $this->creerEleve($manager, $classeArta, ++$matricule, ...$donnees);
        }

        // Comptes de connexion des élèves (établissement 1 et établissement 2),
        // créés dès maintenant : chaque élève et son email sont connus statiquement
        // (contrairement à un identifiant technique auto-incrémenté), ce qui permet
        // de les documenter simplement dans le README.
        foreach (array_merge($elevesData6A, $elevesData6B) as $index => $donnees) {
            $userEleve = $this->creerUser($manager, $donnees[5], $donnees[0], $donnees[1], ['ROLE_ELEVE'], $balbala);
            $tousLesEleves[$index]->setUser($userEleve);
        }
        foreach ($elevesArta as $index => $donnees) {
            $userEleve = $this->creerUser($manager, $donnees[5], $donnees[0], $donnees[1], ['ROLE_ELEVE'], $arta);
            $elevesArtaEntites[$index]->setUser($userEleve);
        }

        // --- Périodes (3 trimestres) pour chaque établissement ----------------
        $periodesBalbala = $this->creerPeriodes($manager, $balbala);
        $this->creerPeriodes($manager, $arta);

        $manager->flush();

        // --- Notes : pour chaque élève, chaque matière affectée à sa classe, ---
        // --- sur les 3 trimestres. Générées puis flushées avant tout calcul. ---
        foreach ([$classe6A, $classe6B] as $classe) {
            $matieresDeLaClasse = [];
            foreach ($classe->getAffectations() as $affectation) {
                $matieresDeLaClasse[] = ['matiere' => $affectation->getMatiere(), 'enseignant' => $affectation->getEnseignant()];
            }

            foreach ($classe->getEleves() as $eleve) {
                foreach ($matieresDeLaClasse as $ligne) {
                    foreach ($periodesBalbala as $periode) {
                        $this->creerNote($manager, $eleve, $ligne['matiere'], $periode, 'Devoir', $ligne['enseignant']->getUser());
                        $this->creerNote($manager, $eleve, $ligne['matiere'], $periode, 'Composition', $ligne['enseignant']->getUser());
                    }
                }
            }
        }

        $manager->flush();

        // --- Bulletins de démonstration -----------------------------------
        // Trimestre 1 : intégralement publié (déjà visible côté élève).
        // Trimestre 2 : validé par l'enseignant, en attente de publication
        //               (permet de démontrer l'action "Publier" du proviseur).
        // Trimestre 3 : uniquement des notes, aucun bulletin généré
        //               (permet de dérouler le parcours complet en démo).
        [$t1, $t2, $t3] = $periodesBalbala;

        foreach ([$classe6A, $classe6B] as $classe) {
            $bulletinsT1 = $this->bulletinGenerator->genererPourClasse($classe, $t1, $ens1->getUser());
            foreach ($bulletinsT1 as $bulletin) {
                $this->workflow->validerParEnseignant($bulletin, $ens1->getUser());
                $this->workflow->publierParProviseur($bulletin, $proviseurBalbala);
            }

            $bulletinsT2 = $this->bulletinGenerator->genererPourClasse($classe, $t2, $ens1->getUser());
            foreach ($bulletinsT2 as $bulletin) {
                $this->workflow->validerParEnseignant($bulletin, $ens1->getUser());
            }
        }

        $manager->flush();
    }

    private function creerEtablissement(ObjectManager $manager, string $nom, string $code, string $ville): Etablissement
    {
        $etab = new Etablissement();
        $etab->setNom($nom);
        $etab->setCode($code);
        $etab->setVille($ville);
        $etab->setAdresse('Adresse fictive, '.$ville);
        $manager->persist($etab);

        return $etab;
    }

    private function creerUser(ObjectManager $manager, string $email, string $nom, string $prenom, array $roles, ?Etablissement $etablissement): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setNom($nom);
        $user->setPrenom($prenom);
        $user->setRoles($roles);
        $user->setEtablissement($etablissement);
        $user->setPassword($this->hasher->hashPassword($user, self::MOT_DE_PASSE_DEMO));
        $manager->persist($user);

        return $user;
    }

    private function creerMatiere(ObjectManager $manager, string $nom, string $code, int $coefficient, ?string $nomArabe): Matiere
    {
        $matiere = new Matiere();
        $matiere->setNom($nom);
        $matiere->setCode($code);
        $matiere->setCoefficient($coefficient);
        $matiere->setNomArabe($nomArabe);
        $manager->persist($matiere);

        return $matiere;
    }

    private function creerClasse(ObjectManager $manager, Etablissement $etablissement, string $nom, string $niveau): Classe
    {
        $classe = new Classe();
        $classe->setEtablissement($etablissement);
        $classe->setNom($nom);
        $classe->setNiveau($niveau);
        $classe->setAnneeScolaire('2025-2026');
        $manager->persist($classe);
        // Doctrine ne maintient pas automatiquement le côté inverse d'une association
        // pour des entités jamais chargées depuis la base : on le fait explicitement
        // ici pour que Classe::getEleves()/getAffectations() soient exploitables
        // immédiatement (sans flush+clear) plus loin dans ce script de fixtures.
        $etablissement->getClasses()->add($classe);

        return $classe;
    }

    private function creerEnseignant(ObjectManager $manager, string $email, string $nom, string $prenom, Etablissement $etablissement): Enseignant
    {
        $user = $this->creerUser($manager, $email, $nom, $prenom, ['ROLE_ENSEIGNANT'], $etablissement);

        $enseignant = new Enseignant();
        $enseignant->setUser($user);
        $manager->persist($enseignant);

        return $enseignant;
    }

    private function creerAffectation(ObjectManager $manager, Enseignant $enseignant, Matiere $matiere, Classe $classe): Affectation
    {
        $affectation = new Affectation();
        $affectation->setEnseignant($enseignant);
        $affectation->setMatiere($matiere);
        $affectation->setClasse($classe);
        $manager->persist($affectation);
        // Voir le commentaire de creerClasse() : synchronisation manuelle du côté inverse.
        $classe->getAffectations()->add($affectation);
        $enseignant->getAffectations()->add($affectation);

        return $affectation;
    }

    private function creerEleve(
        ObjectManager $manager,
        Classe $classe,
        int $matricule,
        string $nom,
        string $prenom,
        string $sexe,
        string $dateNaissance,
        ?string $nomArabe,
        ?string $emailCompteDemo = null,
    ): Eleve {
        $eleve = new Eleve();
        $eleve->setClasse($classe);
        $eleve->setMatricule('DJ-'.$matricule);
        $eleve->setNom($nom);
        $eleve->setPrenom($prenom);
        $eleve->setSexe($sexe);
        $eleve->setDateNaissance(new \DateTimeImmutable($dateNaissance));
        $eleve->setNomArabe($nomArabe);
        $manager->persist($eleve);
        // Voir le commentaire de creerClasse() : synchronisation manuelle du côté inverse.
        $classe->getEleves()->add($eleve);

        return $eleve;
    }

    /** @return Periode[] */
    private function creerPeriodes(ObjectManager $manager, Etablissement $etablissement): array
    {
        $definitions = [
            ['Trimestre 1', 1, '2025-09-01', '2025-12-19'],
            ['Trimestre 2', 2, '2026-01-05', '2026-03-27'],
            ['Trimestre 3', 3, '2026-04-06', '2026-06-25'],
        ];

        $periodes = [];
        foreach ($definitions as [$nom, $ordre, $debut, $fin]) {
            $periode = new Periode();
            $periode->setNom($nom);
            $periode->setOrdre($ordre);
            $periode->setAnneeScolaire('2025-2026');
            $periode->setDateDebut(new \DateTimeImmutable($debut));
            $periode->setDateFin(new \DateTimeImmutable($fin));
            $periode->setEtablissement($etablissement);
            $manager->persist($periode);
            $periodes[] = $periode;
        }

        return $periodes;
    }

    private function creerNote(ObjectManager $manager, Eleve $eleve, Matiere $matiere, Periode $periode, string $type, User $saisiePar): Note
    {
        // Notes pseudo-aléatoires mais reproductibles (mt_srand(42) en tête de load()),
        // légèrement biaisées vers 10-16 pour rester réalistes.
        $valeur = round(min(20, max(4, 12 + (mt_rand(-60, 60) / 10))), 2);

        $note = new Note();
        $note->setEleve($eleve);
        $note->setMatiere($matiere);
        $note->setPeriode($periode);
        $note->setValeur($valeur);
        $note->setTypeEvaluation($type);
        $note->setSaisiePar($saisiePar);
        $manager->persist($note);

        return $note;
    }
}
