<?php

namespace App\Repository;

use App\Entity\Article;
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

    public function moveArticlesFromCategoryTo(Category $fromCategory, Category $toCategory): void
    {
        $this->createQueryBuilder('a')
            ->update(Article::class, 'a')
            ->set('a.category', ':toCategory')
            ->where('a.category = :fromCategory')
            ->setParameter('fromCategory', $fromCategory)
            ->setParameter('toCategory', $toCategory)
            ->getQuery()
            ->execute();
    }
}
