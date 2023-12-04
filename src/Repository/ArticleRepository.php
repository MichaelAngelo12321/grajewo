<?php

namespace App\Repository;

use App\Entity\Article;
use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 *
 * @method Article|null find($id, $lockMode = null, $lockVersion = null)
 * @method Article|null findOneBy(array $criteria, array $orderBy = null)
 * @method Article[]    findAll()
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    public function count(array $criteria = []): int
    {
        $query = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)');

        $this->applyCriteria($query, $criteria);

        return $query->getQuery()
            ->getSingleScalarResult();
    }

    public function findBy(array $criteria, array|null $orderBy = null, $limit = null, $offset = null): array
    {
        $query = $this->createQueryBuilder('a')
            ->orderBy('a.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        $this->applyCriteria($query, $criteria);

        return $query->getQuery()->getResult();
    }

    /**
     * @return Article[] Returns an array of Article objects
     */
    public function findLatestByCategory(Category $category, int $limit, int $offset = 0): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.category = :category')
            ->setParameter('category', $category)
            ->orderBy('a.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function increaseViewsNumber(Article $article): void
    {
        $this->createQueryBuilder('a')
            ->update()
            ->set('a.viewsNumber', 'a.viewsNumber + 1')
            ->where('a.id = :id')
            ->setParameter('id', $article->getId())
            ->getQuery()
            ->execute();
    }

    private function applyCriteria($query, array $criteria): void
    {
        foreach ($criteria as $field => $value) {
            if (!is_array($value)) {
                $query->andWhere("a.$field = :$field")
                    ->setParameter($field, $value);
                continue;
            }

            [$criteriaOperator, $criteriaValue] = $value;

            switch ($criteriaOperator) {
                case 'LIKE':
                    $query->andWhere("a.$field $criteriaOperator :$field")
                        ->setParameter($field, "%$criteriaValue%");
                    break;
                case 'FULLTEXT':
                    $query->andWhere("MATCH(a.name, a.content) AGAINST(:$field boolean) > 0.1")
                        ->setParameter($field, "*$criteriaValue*");
                    break;
            }
        }
    }
}
