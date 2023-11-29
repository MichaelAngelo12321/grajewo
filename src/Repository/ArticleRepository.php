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
 * @method Article[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
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
            ->setFirstResult($offset * $limit)
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
}
