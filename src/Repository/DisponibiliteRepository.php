<?php

namespace App\Repository;

use App\Entity\Disponibilite;
use App\Entity\User;
use Doctrine\DBAL\Types\Types;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Disponibilite>
 */
class DisponibiliteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Disponibilite::class);
    }

    /**
     * @return list<Disponibilite>
     */
    public function findByPsychologue(User $psy): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('IDENTITY(d.psychologue) = :psyId')
            ->setParameter('psyId', $psy->getId())
            ->orderBy('d.date', 'ASC')
            ->addOrderBy('d.heureDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Disponibilite>
     */
    public function findByPsychologueAndDate(User $psy, \DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('IDENTITY(d.psychologue) = :psyId')
            ->andWhere('d.date = :date')
            ->andWhere('d.estLibre = :libre')
            ->setParameter('psyId', $psy->getId())
            ->setParameter('libre', 1)
            ->setParameter('date', $date, Types::DATE_MUTABLE)
            ->orderBy('d.heureDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<string> format Y-m-d
     */
    public function findAvailableDatesByPsychologue(User $psy): array
    {
        $rows = $this->createQueryBuilder('d')
            ->select('d.date AS availableDate')
            ->andWhere('IDENTITY(d.psychologue) = :psyId')
            ->andWhere('d.estLibre = :libre')
            ->andWhere('d.date >= :today')
            ->setParameter('psyId', $psy->getId())
            ->setParameter('libre', 1)
            ->setParameter('today', new \DateTimeImmutable('today'), Types::DATE_MUTABLE)
            ->groupBy('d.date')
            ->orderBy('d.date', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $dates = [];
        foreach ($rows as $row) {
            $date = $row['availableDate'] ?? null;
            if ($date instanceof \DateTimeInterface) {
                $dates[] = $date->format('Y-m-d');
            } elseif (is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}/', $date) === 1) {
                $dates[] = substr($date, 0, 10);
            }
        }

        return $dates;
    }

    public function findOneLibreForPsychologueDateHeure(User $psy, \DateTimeInterface $day, string $heureHi): ?Disponibilite
    {
        if (preg_match('/^\d{2}:\d{2}$/', $heureHi) !== 1) {
            return null;
        }
        foreach ($this->findByPsychologueAndDate($psy, $day) as $d) {
            if (!$d instanceof Disponibilite || !$d->isLibre()) {
                continue;
            }
            $start = $d->getHeureDebut()?->format('H:i');
            if ($start === $heureHi) {
                return $d;
            }
        }

        return null;
    }
}
