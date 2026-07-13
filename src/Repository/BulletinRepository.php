<?php

namespace App\Repository;

use App\Entity\Bulletin;
use App\Entity\Classe;
use App\Entity\Eleve;
use App\Entity\Periode;
use App\Enum\BulletinStatut;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Bulletin>
 */
class BulletinRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bulletin::class);
    }

    public function findOneByEleveEtPeriode(Eleve $eleve, Periode $periode): ?Bulletin
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.eleve = :eleve')
            ->andWhere('b.periode = :periode')
            ->setParameter('eleve', $eleve)
            ->setParameter('periode', $periode)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return Bulletin[] File d'attente de validation/publication pour un établissement. */
    public function findEnAttenteParEtablissement(int $etablissementId, ?BulletinStatut $statut = null): array
    {
        $qb = $this->createQueryBuilder('b')
            ->innerJoin('b.eleve', 'e')
            ->innerJoin('e.classe', 'c')
            ->andWhere('c.etablissement = :etab')
            ->setParameter('etab', $etablissementId)
            ->orderBy('b.dateGeneration', 'DESC');

        if ($statut) {
            $qb->andWhere('b.statut = :statut')->setParameter('statut', $statut);
        } else {
            $qb->andWhere('b.statut != :publie')->setParameter('publie', BulletinStatut::PUBLIE);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Utilisé par la vérification publique d'authenticité (App\Controller\VerificationController).
     * Ne retourne QUE des bulletins publiés : un code de brouillon régénéré ne doit
     * plus jamais matcher (voir BulletinGenerator::genererPourEleve qui l'efface).
     */
    public function findOnePublieParCode(string $code): ?Bulletin
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.codeVerification = :code')
            ->andWhere('b.statut = :publie')
            ->setParameter('code', $code)
            ->setParameter('publie', BulletinStatut::PUBLIE)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return Bulletin[] Bulletins publiés d'un élève (visibles côté élève). */
    public function findPubliesParEleve(Eleve $eleve): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.eleve = :eleve')
            ->andWhere('b.statut = :publie')
            ->setParameter('eleve', $eleve)
            ->setParameter('publie', BulletinStatut::PUBLIE)
            ->innerJoin('b.periode', 'p')
            ->orderBy('p.ordre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return array<int, float> Moyennes générales des bulletins d'une classe pour une période (pour le rang). */
    public function findMoyennesParClasseEtPeriode(Classe $classe, Periode $periode): array
    {
        $resultats = $this->createQueryBuilder('b')
            ->select('e.id as eleveId', 'b.moyenneGenerale as moyenne')
            ->innerJoin('b.eleve', 'e')
            ->andWhere('e.classe = :classe')
            ->andWhere('b.periode = :periode')
            ->setParameter('classe', $classe)
            ->setParameter('periode', $periode)
            ->getQuery()
            ->getArrayResult();

        $moyennes = [];
        foreach ($resultats as $ligne) {
            $moyennes[$ligne['eleveId']] = (float) $ligne['moyenne'];
        }

        return $moyennes;
    }
}
