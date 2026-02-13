<?php

namespace App\Repository;

use App\Entity\TypeRendezVous;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TypeRendezVous>
 */
class TypeRendezVousRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TypeRendezVous::class);
    }

    // Ajoutez vos méthodes personnalisées ici
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.libelle', 'ASC')
            ->getQuery()
            ->getResult();
    }
}