<?php

namespace App\Repository;

use App\Entity\AuditLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuditLog>
 */
final class AuditLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditLog::class);
    }

    /**
     * @param list<int> $entityIds
     * @return array<int, list<AuditLog>> keyed by entityId
     */
    public function findByEntityIdsGrouped(string $entityType, array $entityIds): array
    {
        if (count($entityIds) === 0) {
            return [];
        }

        /** @var list<AuditLog> $logs */
        $logs = $this->createQueryBuilder('a')
            ->andWhere('a.entityType = :t')
            ->andWhere('a.entityId IN (:ids)')
            ->setParameter('t', $entityType)
            ->setParameter('ids', array_map('strval', $entityIds))
            ->orderBy('a.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        $out = [];
        foreach ($logs as $log) {
            $id = $log->getEntityId();
            if ($id === null) {
                continue;
            }
            $key = (int) $id;
            $out[$key] ??= [];
            $out[$key][] = $log;
        }

        return $out;
    }
}

