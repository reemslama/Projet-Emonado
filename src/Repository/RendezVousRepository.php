<?php

namespace App\Repository;

use App\Entity\Disponibilite;
use App\Entity\RendezVous;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
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
     * Statuts qui occupent le créneau (refus libère le créneau).
     *
     * @return list<string>
     */
    public static function statutsOccupantCreneau(): array
    {
        return [RendezVous::STATUT_EN_ATTENTE, RendezVous::STATUT_ACCEPTE];
    }

    /**
     * @return list<string> créneaux "H:i" déjà pris ce jour-là pour ce psychologue
     */
    public function findBookedHeuresForPsychologueOnDate(User $psy, \DateTimeInterface $day): array
    {
        /** @var list<RendezVous> $rdvs */
        $rdvs = $this->createQueryBuilder('r')
            ->join('r.disponibilite', 'd')
            ->addSelect('d')
            ->andWhere('IDENTITY(d.psychologue) = :psyId')
            ->andWhere('d.date = :day')
            ->andWhere('r.statut IN (:st)')
            ->setParameter('psyId', $psy->getId())
            ->setParameter('day', $day, Types::DATE_MUTABLE)
            ->setParameter('st', self::statutsOccupantCreneau())
            ->getQuery()
            ->getResult();

        $out = [];
        foreach ($rdvs as $r) {
            $h = $r->getDisponibilite()?->getHeureDebut();
            if ($h instanceof \DateTimeInterface) {
                $out[] = $h->format('H:i');
            }
        }

        return array_values(array_unique($out));
    }

    public function countActiveForDisponibilite(Disponibilite $dispo): int
    {
        $id = $dispo->getId();
        if ($id === null) {
            return 0;
        }

        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.disponibilite = :dispo')
            ->andWhere('r.statut IN (:st)')
            ->setParameter('dispo', $dispo)
            ->setParameter('st', self::statutsOccupantCreneau())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return RendezVous[]
     */
    public function findBySearchAndSort(?string $search, ?string $sort): array
    {
        return $this->findBySearchAndSortQueryBuilder($search, $sort)->getQuery()->getResult();
    }

    public function findBySearchAndSortQueryBuilder(?string $search, ?string $sort): QueryBuilder
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.type', 't')
            ->addSelect('t')
            ->leftJoin('r.patient', 'p')
            ->leftJoin('r.disponibilite', 'disp');

        if ($search) {
            $qb->andWhere('p.nom LIKE :q OR p.prenom LIKE :q OR p.email LIKE :q OR t.libelle LIKE :q')
               ->setParameter('q', '%' . $search . '%');
        }

        if ($sort === 'nom') {
            $qb->orderBy('p.nom', 'ASC')->addOrderBy('p.prenom', 'ASC');
        } else {
            $qb->orderBy('disp.date', 'DESC')->addOrderBy('disp.heureDebut', 'DESC');
        }

        return $qb;
    }

    /**
     * @return RendezVous[]
     */
    public function findHistoriqueByPatient(User $patient): array
    {
        /** @var list<RendezVous> $result */
        $result = $this->createQueryBuilder('r')
            ->leftJoin('r.disponibilite', 'd')
            ->addSelect('d')
            ->where('r.patient = :patient')
            ->setParameter('patient', $patient)
            ->orderBy('d.date', 'DESC')
            ->addOrderBy('d.heureDebut', 'DESC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function getStatsParMois(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT YEAR(d.date) as annee, MONTH(d.date) as mois, COUNT(r.id) as total
            FROM rendez_vous r
            INNER JOIN disponibilite d ON r.dispo_id = d.id
            WHERE r.statut = :accepte
            GROUP BY annee, mois
            ORDER BY annee DESC, mois DESC
        ';

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery(['accepte' => RendezVous::STATUT_ACCEPTE]);

        return $result->fetchAllAssociative();
    }
}
