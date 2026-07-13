<?php

namespace App\Repository;

use App\Entity\JournalAcces;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JournalAcces>
 */
class JournalAccesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JournalAcces::class);
    }

    /** @return JournalAcces[] Les N dernières entrées, les plus récentes en premier. */
    public function findDernieres(int $limite = 50): array
    {
        return $this->createQueryBuilder('j')
            ->orderBy('j.dateAction', 'DESC')
            ->setMaxResults($limite)
            ->getQuery()
            ->getResult();
    }
}
