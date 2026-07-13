<?php

namespace App\Tests\Security;

use App\Entity\Affectation;
use App\Entity\Bulletin;
use App\Entity\Classe;
use App\Entity\Eleve;
use App\Entity\Enseignant;
use App\Entity\Etablissement;
use App\Entity\Matiere;
use App\Entity\Periode;
use App\Entity\User;
use App\Enum\BulletinStatut;
use App\Security\Voter\BulletinVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Couvre le workflow de validation (brouillon -> validé enseignant -> publié)
 * et les deux invariants RBAC exigés :
 *  (1) un élève n'accède qu'à son propre bulletin, et seulement s'il est publié ;
 *  (2) cloisonnement par établissement pour proviseur/enseignant.
 * Couvre également la règle métier explicite : l'enseignant ne publie JAMAIS.
 */
final class BulletinVoterTest extends TestCase
{
    use EntityReflectionHelper;

    private BulletinVoter $voter;
    private Etablissement $etablissementA;
    private Etablissement $etablissementB;
    private Classe $classeA;
    private Eleve $eleveX;
    private User $userEleveX;
    private User $userEnseignantA;
    private User $userProviseurA;
    private User $userProviseurB;

    protected function setUp(): void
    {
        $this->voter = new BulletinVoter();

        $this->etablissementA = new Etablissement();
        $this->forcerId($this->etablissementA, 1);
        $this->etablissementB = new Etablissement();
        $this->forcerId($this->etablissementB, 2);

        $this->classeA = new Classe();
        $this->classeA->setEtablissement($this->etablissementA);
        $this->forcerId($this->classeA, 10);

        $this->eleveX = new Eleve();
        $this->eleveX->setClasse($this->classeA);
        $this->forcerId($this->eleveX, 100);

        $this->userEleveX = new User();
        $this->userEleveX->setRoles(['ROLE_ELEVE']);
        $this->forcerId($this->userEleveX, 1000);
        $this->eleveX->setUser($this->userEleveX);

        $matiere = new Matiere();
        $this->forcerId($matiere, 500);
        $matiere->setCoefficient(4);

        $this->userEnseignantA = new User();
        $this->userEnseignantA->setRoles(['ROLE_ENSEIGNANT']);
        $this->userEnseignantA->setEtablissement($this->etablissementA);
        $this->forcerId($this->userEnseignantA, 2000);

        $enseignant = new Enseignant();
        $enseignant->setUser($this->userEnseignantA);
        $this->forcerId($enseignant, 200);

        $affectation = new Affectation();
        $affectation->setEnseignant($enseignant);
        $affectation->setMatiere($matiere);
        $affectation->setClasse($this->classeA);
        $enseignant->getAffectations()->add($affectation);

        $this->userProviseurA = new User();
        $this->userProviseurA->setRoles(['ROLE_PROVISEUR']);
        $this->userProviseurA->setEtablissement($this->etablissementA);
        $this->forcerId($this->userProviseurA, 3000);

        $this->userProviseurB = new User();
        $this->userProviseurB->setRoles(['ROLE_PROVISEUR']);
        $this->userProviseurB->setEtablissement($this->etablissementB);
        $this->forcerId($this->userProviseurB, 3001);
    }

    private function bulletin(BulletinStatut $statut): Bulletin
    {
        $periode = new Periode();
        $this->forcerId($periode, 900);

        $bulletin = new Bulletin();
        $bulletin->setEleve($this->eleveX);
        $bulletin->setPeriode($periode);
        $bulletin->setStatut($statut);
        $this->forcerId($bulletin, 5000);

        return $bulletin;
    }

    public function testInvariant1_UnEleveVoitSonProprePropreBulletinPublie(): void
    {
        $bulletin = $this->bulletin(BulletinStatut::PUBLIE);
        $token = new UsernamePasswordToken($this->userEleveX, 'main', $this->userEleveX->getRoles());

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $bulletin, [BulletinVoter::VIEW])
        );
    }

    public function testInvariant1_UnEleveNeVoitPasSonBulletinNonPublie(): void
    {
        $bulletin = $this->bulletin(BulletinStatut::VALIDE_ENSEIGNANT);
        $token = new UsernamePasswordToken($this->userEleveX, 'main', $this->userEleveX->getRoles());

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $bulletin, [BulletinVoter::VIEW])
        );
    }

    public function testInvariant1_UnAutreEleveNeVoitJamaisCeBulletin(): void
    {
        $bulletin = $this->bulletin(BulletinStatut::PUBLIE);

        $autreEleveUser = new User();
        $autreEleveUser->setRoles(['ROLE_ELEVE']);
        $this->forcerId($autreEleveUser, 1002);
        $token = new UsernamePasswordToken($autreEleveUser, 'main', $autreEleveUser->getRoles());

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $bulletin, [BulletinVoter::VIEW])
        );
    }

    public function testEnseignantPeutGenererEtValiderMaisJamaisPublier(): void
    {
        $bulletin = $this->bulletin(BulletinStatut::BROUILLON);
        $token = new UsernamePasswordToken($this->userEnseignantA, 'main', $this->userEnseignantA->getRoles());

        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $bulletin, [BulletinVoter::GENERATE]));
        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $bulletin, [BulletinVoter::VALIDATE]));
        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $bulletin, [BulletinVoter::PUBLISH]),
            "Règle métier violée : l'enseignant ne doit JAMAIS pouvoir publier un bulletin."
        );
    }

    public function testProviseurDuMemeEtablissementPeutPublier(): void
    {
        $bulletin = $this->bulletin(BulletinStatut::VALIDE_ENSEIGNANT);
        $token = new UsernamePasswordToken($this->userProviseurA, 'main', $this->userProviseurA->getRoles());

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $bulletin, [BulletinVoter::PUBLISH])
        );
    }

    public function testInvariant2_ProviseurDUnAutreEtablissementNePeutPasPublier(): void
    {
        $bulletin = $this->bulletin(BulletinStatut::VALIDE_ENSEIGNANT);
        $token = new UsernamePasswordToken($this->userProviseurB, 'main', $this->userProviseurB->getRoles());

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $bulletin, [BulletinVoter::PUBLISH])
        );
    }

    public function testInvariant2_ProviseurDUnAutreEtablissementNeVoitMemePasLeBulletin(): void
    {
        $bulletin = $this->bulletin(BulletinStatut::PUBLIE);
        $token = new UsernamePasswordToken($this->userProviseurB, 'main', $this->userProviseurB->getRoles());

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $bulletin, [BulletinVoter::VIEW])
        );
    }
}
