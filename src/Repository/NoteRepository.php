<?php

namespace App\Repository;

use App\Entity\Eleve;
use App\Entity\Matiere;
use App\Entity\Note;
use App\Entity\Periode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Note>
 */
class NoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Note::class);
    }

    /** @return Note[] */
    public function findPourEleveMatierePeriode(Eleve $eleve, Matiere $matiere, Periode $periode): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.eleve = :eleve')
            ->andWhere('n.matiere = :matiere')
            ->andWhere('n.periode = :periode')
            ->setParameter('eleve', $eleve)
            ->setParameter('matiere', $matiere)
            ->setParameter('periode', $periode)
            ->orderBy('n.dateSaisie', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return Note[] Toutes les notes d'un élève sur une période, toutes matières confondues. */
    public function findPourElevePeriode(Eleve $eleve, Periode $periode): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.eleve = :eleve')
            ->andWhere('n.periode = :periode')
            ->setParameter('eleve', $eleve)
            ->setParameter('periode', $periode)
            ->getQuery()
            ->getResult();
    }
}
