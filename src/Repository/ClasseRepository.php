<?php

namespace App\Repository;

use App\Entity\Classe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Classe>
 */
class ClasseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Classe::class);
    }

    /** @return Classe[] */
    public function findByEtablissement(int $etablissementId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.etablissement = :etab')
            ->setParameter('etab', $etablissementId)
            ->orderBy('c.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
