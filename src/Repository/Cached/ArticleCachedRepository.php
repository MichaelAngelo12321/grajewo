<?php

declare(strict_types=1);

namespace App\Repository\Cached;

use App\Entity\Category;
use App\Enum\ArticleStatus;
use App\Repository\ArticleRepository;
use DateInterval;
use DateTimeImmutable;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class ArticleCachedRepository
{
    public function __construct(
        private ArticleRepository $articleRepository,
        private CacheInterface $cache,
    ) {
    }

    public function findEventsFromThisMonth(): array
    {
        $cacheKey = CacheKeyPrefix::ARTICLE_EVENTS_FROM_THIS_MONTH;

        return $this->cache->get($cacheKey, function (ItemInterface $item) {
            $item->expiresAfter(DateInterval::createFromDateString('last day of this month'));

            $groupedEvents = [];
            $events = $this->articleRepository->createQueryBuilder('a')
                ->where('a.status = :status')
                ->setParameter('status', ArticleStatus::PUBLISHED)
                ->andWhere('a.isEvent = :isEvent')
                ->setParameter('isEvent', true)
                ->andWhere('DATE(a.eventDateTime) >= DATE(:eventDate)')
                ->setParameter('eventDate', new DateTimeImmutable('first day of this month'))
                ->andWhere('DATE(a.eventDateTime) <= DATE(:eventDateEnd)')
                ->setParameter('eventDateEnd', new DateTimeImmutable('last day of this month'))
                ->orderBy('a.eventDateTime', 'ASC')
                ->getQuery()
                ->getResult();

            foreach ($events as $event) {
                if (!array_key_exists($event->getEventDateTime()->format('d'), $groupedEvents)) {
                    $groupedEvents[$event->getEventDateTime()->format('d')] = [];
                }

                $groupedEvents[$event->getEventDateTime()->format('d')][] = $event;
            }

            return $groupedEvents;
        });
    }

    public function findLatestArticlesFromCategory(Category $category, int $limit = 10): array
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
