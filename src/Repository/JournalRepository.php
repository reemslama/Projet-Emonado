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

        // Initialiser toutes les humeurs à 0
        $stats = [
            'heureux' => 0,
            'calme' => 0,
            'SOS' => 0,
            'en colere' => 0,
        ];

        foreach ($result as $row) {
            $stats[$row['humeur']] = (int) $row['total'];
        }

        return $stats;
    }

    /**
     * Données pour graphique d'évolution des humeurs (journal) sur les N derniers jours.
     * Retourne une liste [ ['date' => 'Y-m-d', 'score' => 1-4, 'humeur' => string], ... ] triée par date.
     * Score: SOS=1, en colere=2, calme=3, heureux=4.
     */
    public function getEvolutionForUser(User $user, int $days = 90): array
    {
        $since = (new \DateTime())->modify("-{$days} days");
        $qb = $this->createQueryBuilder('j')
            ->andWhere('j.user = :user')
            ->andWhere('j.dateCreation >= :since')
            ->setParameter('user', $user)
            ->setParameter('since', $since)
            ->orderBy('j.dateCreation', 'ASC');
        $journals = $qb->getQuery()->getResult();
        $scores = ['SOS' => 1, 'en colere' => 2, 'calme' => 3, 'heureux' => 4];
        $out = [];
        foreach ($journals as $j) {
            $out[] = [
                'date' => $j->getDateCreation()->format('Y-m-d'),
                'score' => $scores[$j->getHumeur()] ?? 2,
                'humeur' => $j->getHumeur(),
            ];
        }
        return $out;
    }
}
