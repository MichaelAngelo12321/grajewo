<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 *
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function updateArticlesCount(Category $category): void
    {
        $this->createQueryBuilder('c')
            ->update()
            ->set('c.articlesNumber', '(SELECT COUNT(a.id) FROM App\Entity\Article a WHERE a.category = c)')
            ->where('c.id = :id')
            ->setParameter('id', $category->getId())
            ->getQuery()
            ->execute();
    }
}
