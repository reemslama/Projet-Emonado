<?php

namespace App\Repository;

use App\Entity\Journal;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Journal>
 */
class JournalRepository extends ServiceEntityRepository
{
    private const MOODS = ['heureux', 'calme', 'sos', 'en colere'];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Journal::class);
    }

    public function searchAndSortByUser(
        User $user,
        ?string $keyword,
        ?string $sort
    ): array {
        $qb = $this->createQueryBuilder('j')
            ->andWhere('j.user = :user')
            ->setParameter('user', $user);

        $this->applyKeywordFilter($qb, $keyword);

        if ($sort === 'old') {
            $qb->orderBy('j.dateCreation', 'ASC');
        } else {
            $qb->orderBy('j.dateCreation', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }

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

    public function countByHumeurForUserBetween(
        User $user,
        \DateTimeInterface $start,
        \DateTimeInterface $end
    ): array {
        $result = $this->createQueryBuilder('j')
            ->select('j.humeur, COUNT(j.id) AS total')
            ->where('j.user = :user')
            ->andWhere('j.dateCreation >= :start')
            ->andWhere('j.dateCreation < :end')
            ->setParameter('user', $user)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->groupBy('j.humeur')
            ->getQuery()
            ->getResult();

        return $this->buildStatsArray($result);
    }

    public function countByHumeurAll(): array
    {
        $result = $this->createQueryBuilder('j')
            ->select('j.humeur, COUNT(j.id) AS total')
            ->groupBy('j.humeur')
            ->getQuery()
            ->getResult();

        return $this->buildStatsArray($result);
    }

    public function searchAndSortAll(
        ?string $keyword,
        ?string $sort
    ): array {
        $qb = $this->createQueryBuilder('j');

        $this->applyKeywordFilter($qb, $keyword);

        if ($sort === 'old') {
            $qb->orderBy('j.dateCreation', 'ASC');
        } else {
            $qb->orderBy('j.dateCreation', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }

    public function countPendingVoiceCases(): int
    {
        return (int) $this->createQueryBuilder('j')
            ->select('COUNT(j.id)')
            ->andWhere('j.inputMode = :mode')
            ->andWhere('j.psychologueReviewedAt IS NULL')
            ->setParameter('mode', 'voice')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Journal[]
     */
    public function findPendingVoiceCases(): array
    {
        return $this->createQueryBuilder('j')
            ->leftJoin('j.user', 'u')
            ->addSelect('u')
            ->andWhere('j.inputMode = :mode')
            ->andWhere('j.psychologueReviewedAt IS NULL')
            ->setParameter('mode', 'voice')
            ->orderBy('j.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array<int, array{humeur: string, total: string|int}> $result
     */
    private function buildStatsArray(array $result): array
    {
        $stats = [
            'heureux' => 0,
            'calme' => 0,
            'SOS' => 0,
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

    private function applyKeywordFilter(QueryBuilder $qb, ?string $keyword): void
    {
        $keyword = trim((string) $keyword);
        if ($keyword === '') {
            return;
        }

        $normalized = mb_strtolower($keyword);
        if (in_array($normalized, self::MOODS, true)) {
            if ($normalized === 'sos') {
                $qb->andWhere('LOWER(j.humeur) = :mood')
                    ->setParameter('mood', 'sos');
            } else {
                $qb->andWhere('LOWER(j.humeur) = :mood')
                    ->setParameter('mood', $normalized);
            }
            return;
        }

        $qb->andWhere('j.humeur LIKE :kw OR j.contenu LIKE :kw')
            ->setParameter('kw', '%' . $keyword . '%');
    }
}
