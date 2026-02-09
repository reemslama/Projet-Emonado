<?php

namespace App\Repository;

use App\Entity\Question;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class QuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Question::class);
    }

    /**
     * Récupère toutes les catégories distinctes
     * @return array
     */
    public function findAllCategories(): array
    {
        $result = $this->createQueryBuilder('q')
            ->select('DISTINCT q.categorie')
            ->where('q.categorie IS NOT NULL')
            ->orderBy('q.categorie', 'ASC')
            ->getQuery()
            ->getResult();

        return array_column($result, 'categorie');
    }

    /**
     * Filtre les questions par catégorie
     * @param string|null $categorie
     * @return Question[]
     */
    public function findByCategorie(?string $categorie): array
    {
        if (!$categorie || $categorie === 'all') {
            return $this->findBy([], ['ordre' => 'ASC']);
        }

        return $this->findBy(['categorie' => $categorie], ['ordre' => 'ASC']);
    }

    /**
     * Compte les questions par catégorie
     * @return array
     */
    public function countByCategorie(): array
    {
        return $this->createQueryBuilder('q')
            ->select('q.categorie, COUNT(q.id) as total')
            ->groupBy('q.categorie')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des questions par mot-clé dans le texte
     * @param string $keyword
     * @return Question[]
     */
    public function searchByKeyword(string $keyword): array
    {
        return $this->createQueryBuilder('q')
            ->where('q.texte LIKE :keyword')
            ->orWhere('q.categorie LIKE :keyword')
            ->orWhere('q.typeQuestion LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->orderBy('q.ordre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche avancée avec filtre de catégorie
     * @param string $keyword
     * @param string|null $category
     * @return Question[]
     */
    public function searchByKeywordAndCategory(string $keyword, ?string $category = null): array
    {
        $qb = $this->createQueryBuilder('q')
            ->where('q.texte LIKE :keyword')
            ->orWhere('q.typeQuestion LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%');

        if ($category && $category !== 'all') {
            $qb->andWhere('q.categorie = :category')
               ->setParameter('category', $category);
        }

        return $qb->orderBy('q.ordre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si une question existe déjà avec ce texte
     * @param string $texte
     * @return bool
     */
    public function existsByTexte(string $texte): bool
    {
        $count = $this->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->where('q.texte = :texte')
            ->setParameter('texte', $texte)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}