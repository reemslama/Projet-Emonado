<?php

namespace App\Repository;

use App\Entity\Journal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Journal>
 */
class JournalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Journal::class);
    }

    public function searchAndSort(?string $keyword, ?string $sort): array
{
    $qb = $this->createQueryBuilder('j');

    // 🔍 Recherche
    if ($keyword) {
        $qb->andWhere('j.humeur LIKE :kw OR j.contenu LIKE :kw')
           ->setParameter('kw', '%' . $keyword . '%');
    }

    // 🔽 Tri
    if ($sort === 'old') {
        $qb->orderBy('j.dateCreation', 'ASC');
    } else {
        // recent par défaut
        $qb->orderBy('j.dateCreation', 'DESC');
    }

    return $qb->getQuery()->getResult();
}


    //    /**
    //     * @return Journal[] Returns an array of Journal objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('j')
    //            ->andWhere('j.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('j.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Journal
    //    {
    //        return $this->createQueryBuilder('j')
    //            ->andWhere('j.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    public function countByHumeur(): array
    {
        $result = $this->createQueryBuilder('j')
            ->select('j.humeur, COUNT(j.id) AS total')
            ->where('j.humeur IN (:humeurs)')
            ->setParameter('humeurs', [
                'heureux',
                'SOS',
                'en colere',
                'calme'
            ])
            ->groupBy('j.humeur')
            ->getQuery()
            ->getResult();

        // Initialisation (évite les valeurs manquantes)
        $stats = [
            'heureux'   => 0,
            'SOS'       => 0,
            'en colere' => 0,
            'calme'     => 0,
        ];

        foreach ($result as $row) {
            $stats[$row['humeur']] = (int) $row['total'];
        }

        return $stats;
    }

    }