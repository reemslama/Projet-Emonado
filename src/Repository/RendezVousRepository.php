<?php

namespace App\Repository;

use App\Entity\RendezVous;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

        return $qb->getQuery()->getResult();
    }
}