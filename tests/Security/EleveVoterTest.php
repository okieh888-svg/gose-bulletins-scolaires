<?php

namespace App\Tests\Security;

use App\Entity\Affectation;
use App\Entity\Classe;
use App\Entity\Eleve;
use App\Entity\Enseignant;
use App\Entity\Etablissement;
use App\Entity\Matiere;
use App\Entity\User;
use App\Security\Voter\EleveVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Vérifie les DEUX invariants RBAC exigés sur les données d'un élève :
 *  (1) un élève n'accède qu'à ses propres données ;
 *  (2) cloisonnement du personnel (proviseur/enseignant) par établissement.
 */
final class EleveVoterTest extends TestCase
{
    use EntityReflectionHelper;

    private EleveVoter $voter;
    private Etablissement $etablissementA;
    private Etablissement $etablissementB;
    private Classe $classeA;
    private Eleve $eleveX;
    private User $userEleveX;
    private Eleve $eleveY;

    protected function setUp(): void
    {
        $this->voter = new EleveVoter();

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

        $this->eleveY = new Eleve();
        $this->eleveY->setClasse($this->classeA);
        $this->forcerId($this->eleveY, 101);
        $userEleveY = new User();
        $userEleveY->setRoles(['ROLE_ELEVE']);
        $this->forcerId($userEleveY, 1001);
        $this->eleveY->setUser($userEleveY);
    }

    public function testUnEleveAccedeASonPropreProfil(): void
    {
        $token = new UsernamePasswordToken($this->userEleveX, 'main', $this->userEleveX->getRoles());

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $this->eleveX, [EleveVoter::VIEW])
        );
    }

    public function testInvariant1_UnEleveNAccedePasAuProfilDUnAutreEleve(): void
    {
        $token = new UsernamePasswordToken($this->userEleveX, 'main', $this->userEleveX->getRoles());

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $this->eleveY, [EleveVoter::VIEW])
        );
    }

    public function testUnEnseignantDeLaClasseAccedeAuProfilDeLEleve(): void
    {
        $matiere = new Matiere();
        $this->forcerId($matiere, 500);

        $userEnseignant = new User();
        $userEnseignant->setRoles(['ROLE_ENSEIGNANT']);
        $this->forcerId($userEnseignant, 2000);

        $enseignant = new Enseignant();
        $enseignant->setUser($userEnseignant);
        $this->forcerId($enseignant, 200);

        $affectation = new Affectation();
        $affectation->setEnseignant($enseignant);
        $affectation->setMatiere($matiere);
        $affectation->setClasse($this->classeA);
        $enseignant->getAffectations()->add($affectation);

        $token = new UsernamePasswordToken($userEnseignant, 'main', $userEnseignant->getRoles());

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $this->eleveX, [EleveVoter::VIEW])
        );
    }

    public function testInvariant2_UnProviseurDUnAutreEtablissementNAccedePasALEleve(): void
    {
        $userProviseurB = new User();
        $userProviseurB->setRoles(['ROLE_PROVISEUR']);
        $userProviseurB->setEtablissement($this->etablissementB);
        $this->forcerId($userProviseurB, 3000);

        $token = new UsernamePasswordToken($userProviseurB, 'main', $userProviseurB->getRoles());

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $this->eleveX, [EleveVoter::VIEW])
        );
    }

    public function testUnProviseurDuMemeEtablissementAccedeALEleve(): void
    {
        $userProviseurA = new User();
        $userProviseurA->setRoles(['ROLE_PROVISEUR']);
        $userProviseurA->setEtablissement($this->etablissementA);
        $this->forcerId($userProviseurA, 3001);

        $token = new UsernamePasswordToken($userProviseurA, 'main', $userProviseurA->getRoles());

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $this->eleveX, [EleveVoter::VIEW])
        );
    }
}
