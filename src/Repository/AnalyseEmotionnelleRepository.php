<?php

namespace App\Repository;

use App\Entity\AnalyseEmotionnelle;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AnalyseEmotionnelle>
 */
class AnalyseEmotionnelleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnalyseEmotionnelle::class);
    }

    /**
     * @return AnalyseEmotionnelle[]
     */
    public function findForUserContext(User $user, bool $canViewAll): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.journal', 'j')
            ->addSelect('j')
            ->orderBy('a.dateAnalyse', 'DESC');

        if (!$canViewAll) {
            $qb->andWhere('j.user = :user')
                ->setParameter('user', $user);
        }

        return $qb->getQuery()->getResult();
    }

    public function findOneByJournalId(int $journalId): ?AnalyseEmotionnelle
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.journal', 'j')
            ->addSelect('j')
            ->andWhere('j.id = :journalId')
            ->setParameter('journalId', $journalId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return AnalyseEmotionnelle[]
     */
    public function findRecentForUser(User $user, int $limit = 7): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.journal', 'j')
            ->addSelect('j')
            ->andWhere('j.user = :user')
            ->setParameter('user', $user)
            ->orderBy('a.dateAnalyse', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, array{user: User, analyses: AnalyseEmotionnelle[]}>
     */
    public function findRecentGroupedByUser(int $perUser = 7): array
    {
        $rows = $this->createQueryBuilder('a')
            ->leftJoin('a.journal', 'j')
            ->leftJoin('j.user', 'u')
            ->addSelect('j', 'u')
            ->andWhere('u.id IS NOT NULL')
            ->orderBy('u.id', 'ASC')
            ->addOrderBy('a.dateAnalyse', 'DESC')
            ->getQuery()
            ->getResult();

        $grouped = [];
        foreach ($rows as $analyse) {
            $journal = $analyse->getJournal();
            $owner = $journal?->getUser();
            if (!$owner) {
                continue;
            }

            $ownerId = (int) $owner->getId();
            if (!isset($grouped[$ownerId])) {
                $grouped[$ownerId] = [
                    'user' => $owner,
                    'analyses' => [],
                ];
            }

            if (count($grouped[$ownerId]['analyses']) < $perUser) {
                $grouped[$ownerId]['analyses'][] = $analyse;
            }
        }

        return array_values($grouped);
    }

    //    /**
    //     * @return AnalyseEmotionnelle[] Returns an array of AnalyseEmotionnelle objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?AnalyseEmotionnelle
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
