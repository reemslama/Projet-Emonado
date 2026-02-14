<?php

namespace App\Repository;

use App\Entity\Question;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Question>
 */
class QuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Question::class);
    }

    /**
     * Récupère toutes les catégories distinctes (non nulles)
     *
     * @return array<string>
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
     * Récupère les questions par catégorie (ou toutes si 'all' ou null)
     *
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
     * Compte le nombre de questions par catégorie
     *
     * @return array<array{categorie: string|null, total: int}>
     */
    public function countByCategorie(): array
    {
        return $this->createQueryBuilder('q')
            ->select('q.categorie, COUNT(q.id) as total')
            ->groupBy('q.categorie')
            ->orderBy('q.categorie', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des questions par mot-clé (texte, catégorie ou type)
     *
     * @param string $keyword
     * @return Question[]
     */
    public function searchByKeyword(string $keyword): array
    {
        $keyword = '%' . trim($keyword) . '%';

        return $this->createQueryBuilder('q')
            ->where('LOWER(q.texte) LIKE LOWER(:keyword)')
            ->orWhere('LOWER(q.categorie) LIKE LOWER(:keyword)')
            ->orWhere('LOWER(q.typeQuestion) LIKE LOWER(:keyword)')
            ->setParameter('keyword', $keyword)
            ->orderBy('q.ordre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche avancée : mot-clé + filtre catégorie optionnel
     *
     * @param string      $keyword
     * @param string|null $category
     * @return Question[]
     */
    public function searchByKeywordAndCategory(string $keyword, ?string $category = null): array
    {
        $qb = $this->createQueryBuilder('q')
            ->where('LOWER(q.texte) LIKE LOWER(:keyword)')
            ->orWhere('LOWER(q.typeQuestion) LIKE LOWER(:keyword)')
            ->setParameter('keyword', '%' . trim($keyword) . '%');

        if ($category && $category !== 'all') {
            $qb->andWhere('q.categorie = :category')
               ->setParameter('category', $category);
        }

        return $qb->orderBy('q.ordre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si une question existe déjà avec ce texte (insensible à la casse et espaces)
     * Retourne false si le texte est null ou vide
     *
     * @param string|null $texte
     * @return bool
     */
    public function existsByTexte(?string $texte): bool
    {
        if ($texte === null || trim($texte) === '') {
            return false;
        }

        $count = $this->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->where('LOWER(TRIM(q.texte)) = LOWER(:texte)')
            ->setParameter('texte', trim($texte))
            ->getQuery()
            ->getSingleScalarResult();

        return (int)$count > 0;
    }
}