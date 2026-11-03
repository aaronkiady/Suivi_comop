<?php

namespace App\Repository;

use App\Entity\Comop;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comop>
 */
class ComopRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comop::class);
    }

    /**
     * Recherche avec filtres optionnels : statut et plage de dates d'insertion.
     *
     * @return Comop[]
     */
    public function findByFilters(?string $statut = null, ?\DateTimeInterface $dateDebut = null, ?\DateTimeInterface $dateFin = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->orderBy('c.dateInsertion', 'DESC');

        if ($statut !== null && $statut !== '') {
            $qb->andWhere('c.statut = :statut')
               ->setParameter('statut', $statut);
        }

        if ($dateDebut !== null) {
            $qb->andWhere('c.dateInsertion >= :dateDebut')
               ->setParameter('dateDebut', $dateDebut->format('Y-m-d 00:00:00'));
        }

        if ($dateFin !== null) {
            $qb->andWhere('c.dateInsertion <= :dateFin')
               ->setParameter('dateFin', $dateFin->format('Y-m-d 23:59:59'));
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Liste des statuts distincts (pour le select du filtre).
     *
     * @return string[]
     */
    public function findDistinctStatuts(): array
    {
        $result = $this->createQueryBuilder('c')
            ->select('DISTINCT c.statut')
            ->andWhere('c.statut IS NOT NULL')
            ->andWhere('c.statut != :empty')
            ->setParameter('empty', '')
            ->orderBy('c.statut', 'ASC')
            ->getQuery()
            ->getScalarResult();

        return array_column($result, 'statut');
    }
}