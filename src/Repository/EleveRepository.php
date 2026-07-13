<?php

namespace App\Repository;

use App\Entity\Classe;
use App\Entity\Eleve;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Eleve>
 */
class EleveRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Eleve::class);
    }

    /** @return Eleve[] Élèves d'une classe, triés par nom (utilisé pour le classement). */
    public function findByClasseTriParNom(Classe $classe): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.classe = :classe')
            ->setParameter('classe', $classe)
            ->orderBy('e.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
