<?php

namespace App\Repository;

use App\Entity\Journal;
use App\Entity\User;
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

    /**
     * 🔐 Journaux d’un utilisateur seulement
     */
    public function searchAndSortByUser(
        User $user,
        ?string $keyword,
        ?string $sort
    ): array {
        $qb = $this->createQueryBuilder('j')
            ->andWhere('j.user = :user')
            ->setParameter('user', $user);

        // 🔍 Recherche
        if ($keyword) {
            $qb->andWhere('j.humeur LIKE :kw OR j.contenu LIKE :kw')
               ->setParameter('kw', '%' . $keyword . '%');
        }

        // 🔽 Tri
        if ($sort === 'old') {
            $qb->orderBy('j.dateCreation', 'ASC');
        } else {
            $qb->orderBy('j.dateCreation', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 📊 Statistiques des humeurs par utilisateur
     */
    public function countByHumeurForUser(User $user): array
    {
        $result = $this->createQueryBuilder('j')
            ->select('j.humeur, COUNT(j.id) AS total')
            ->where('j.user = :user')
            ->setParameter('user', $user)
            ->groupBy('j.humeur')
            ->getQuery()
            ->getResult();

        return $this->buildStatsArray($result);
    }

    /**
     * 📊 Statistiques des humeurs sur tous les journaux (vue admin / psychologue)
     */
    public function countByHumeurAll(): array
    {
        $result = $this->createQueryBuilder('j')
            ->select('j.humeur, COUNT(j.id) AS total')
            ->groupBy('j.humeur')
            ->getQuery()
            ->getResult();

        return $this->buildStatsArray($result);
    }

    /**
     * 🔍 Recherche + tri sur tous les journaux (vue admin / psychologue)
     */
    public function searchAndSortAll(
        ?string $keyword,
        ?string $sort
    ): array {
        $qb = $this->createQueryBuilder('j');

        if ($keyword) {
            $qb->andWhere('j.humeur LIKE :kw OR j.contenu LIKE :kw')
               ->setParameter('kw', '%' . $keyword . '%');
        }

        if ($sort === 'old') {
            $qb->orderBy('j.dateCreation', 'ASC');
        } else {
            $qb->orderBy('j.dateCreation', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 🧮 Construit le tableau de stats standardisé
     *
     * @param array<int, array{humeur: string, total: string|int}> $result
     */
    private function buildStatsArray(array $result): array
    {
        // Initialiser toutes les humeurs à 0
        $stats = [
            'heureux'   => 0,
            'calme'     => 0,
            'SOS'       => 0,
            'en colere' => 0,
        ];

        foreach ($result as $row) {
            $humeur = $row['humeur'];
            if (array_key_exists($humeur, $stats)) {
                $stats[$humeur] = (int) $row['total'];
            }
        }

        return $stats;
    }
}
