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

    /**
     * Tâche 4 : Fonctionnalité de Recherche
     * Cette méthode filtre les rendez-vous par nom de patient ou de psychologue.
     */
    public function searchByTerm(string $term): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.nom_patient LIKE :term')
            ->orWhere('r.nom_psychologue LIKE :term')
            ->setParameter('term', '%' . $term . '%')
            ->orderBy('r.date', 'ASC') // Ajoute aussi le tri par date ici
            ->getQuery()
            ->getResult();
    }
}