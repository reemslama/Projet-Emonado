<?php

namespace App\Repository;

use App\Entity\RendezVous;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RendezVous>
 */
class RendezVousRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RendezVous::class);
    }

    /**
     * @return RendezVous[]
     */
    public function findBySearchAndSort(?string $search, ?string $sort): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.type', 't')
            ->addSelect('t');

        if ($search) {
            $qb->andWhere('r.nomPatient LIKE :q OR r.cin LIKE :q')
               ->setParameter('q', '%' . $search . '%');
        }

        if ($sort === 'nom') {
            $qb->orderBy('r.nomPatient', 'ASC');
        } else {
            $qb->orderBy('r.date', 'DESC');
        }

        /** @var list<RendezVous> $result */
        $result = $qb->getQuery()->getResult();

        return $result;
    }

    /**
     * @return RendezVous[]
     */
    public function findHistoriqueByPatient(User $patient): array
    {
        /** @var list<RendezVous> $result */
        $result = $this->createQueryBuilder('r')
            ->where('r.patient = :patient')
            ->setParameter('patient', $patient)
            ->orderBy('r.date', 'DESC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function getStatsParMois(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT YEAR(r.date) as annee, MONTH(r.date) as mois, COUNT(r.id) as total
            FROM rendez_vous r
            GROUP BY annee, mois
            ORDER BY annee DESC, mois DESC
        ';

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery();

        return $result->fetchAllAssociative();
    }
    public function findBySearchAndSortQueryBuilder(?string $search, ?string $sort): QueryBuilder
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.type', 't')
            ->addSelect('t');

        if ($search) {
            $qb->andWhere('r.nomPatient LIKE :q OR r.cin LIKE :q')
               ->setParameter('q', '%' . $search . '%');
        }

        if ($sort === 'nom') {
            $qb->orderBy('r.nomPatient', 'ASC');
        } else {
            $qb->orderBy('r.date', 'DESC');
        }

        return $qb;
    }
}
