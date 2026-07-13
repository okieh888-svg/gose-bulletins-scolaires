<?php

namespace App\Repository;

use App\Entity\Periode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Periode>
 */
class PeriodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Periode::class);
    }

    /** @return Periode[] */
    public function findByEtablissementOrdonnees(int $etablissementId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.etablissement = :etab')
            ->setParameter('etab', $etablissementId)
            ->orderBy('p.ordre', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
