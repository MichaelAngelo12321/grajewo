<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Category;
use DateInterval;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class ArticleCachedRepository
{
    public function __construct(
        private ArticleRepository $articleRepository,
        private CacheInterface $cache,
    ) {
    }

    public function findLatestArticlesFromCategory(Category $category, int $limit = 10)
    {
        $cacheKey = 'latest_articles_category_' . $category->getId() . '_limit_' . $limit;

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($category, $limit) {
            $item->expiresAfter(DateInterval::createFromDateString('1 hour'));

            return $this->articleRepository->createQueryBuilder('a')
                ->addSelect('c')
                ->join('a.category', 'c')
                ->where('a.category = :category')
                ->setParameter('category', $category)
                ->orderBy('a.createdAt', 'DESC')
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();
            },
        );
    }

    public function findMostPopularArticles(int $limit = 5): array
    {
        $cacheKey = 'most_popular_articles_limit_' . $limit;

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($limit) {
            $item->expiresAfter(DateInterval::createFromDateString('10 second'));

            return $this->articleRepository->createQueryBuilder('a')
                ->addSelect('c')
                ->join('a.category', 'c')
                ->orderBy('a.viewsNumber', 'DESC')
                ->where("a.createdAt > DATE_SUB(CURRENT_DATE(), 3, 'DAY')")
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();
        });
    }
}
