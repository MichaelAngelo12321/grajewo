<?php

declare(strict_types=1);

namespace App\Repository\Cached;

use App\Entity\Category;
use App\Enum\ArticleStatus;
use App\Repository\ArticleRepository;
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
        $cacheKey = CacheKeyPrefix::ARTICLE_LATEST_FROM_CATEGORY . $category->getId() . '_limit_' . $limit;

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($category, $limit) {
            $item->expiresAfter(DateInterval::createFromDateString('1 hour'));

            return $this->articleRepository->createQueryBuilder('a')
                ->addSelect('c')
                ->join('a.category', 'c')
                ->where('a.category = :category')
                ->setParameter('category', $category)
                ->andWhere('a.status = :status')
                ->setParameter('status', ArticleStatus::PUBLISHED)
                ->orderBy('a.updatedAt', 'DESC')
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();
            },
        );
    }

    public function findMostPopularArticles(int $limit = 5): array
    {
        $cacheKey = CacheKeyPrefix::ARTICLE_MOST_POPULAR . $limit;

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($limit) {
            $item->expiresAfter(DateInterval::createFromDateString('1 hour'));

            return $this->articleRepository->createQueryBuilder('a')
                ->addSelect('c')
                ->join('a.category', 'c')
                ->orderBy('a.viewsNumber', 'DESC')
                ->where("a.createdAt > DATE_SUB(CURRENT_DATE(), 5, 'DAY')")
                ->andWhere('a.status = :status')
                ->setParameter('status', ArticleStatus::PUBLISHED)
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();
        });
    }
}
