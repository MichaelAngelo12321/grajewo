<?php

declare(strict_types=1);

namespace App\Repository\Cached;

use App\Enum\ArticleStatus;
use App\Repository\ArticleCommentRepository;
use DateInterval;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class ArticleCommentCachedRepository
{
    public function __construct(
        private CacheInterface $cache,
        private ArticleCommentRepository $articleCommentRepository,
    ) {
    }

    public function findLatest(int $count = 5): array
    {
        return $this->cache->get(
            CacheKeyPrefix::ARTICLE_COMMENT_LAST . $count,
            function (ItemInterface $item) use ($count) {
                $item->expiresAfter(DateInterval::createFromDateString('24 hour'));

                $qb = $this->articleCommentRepository->createQueryBuilder('c')
                    ->addSelect('a', 'cat')
                    ->join('c.article', 'a')
                    ->join('a.category', 'cat')
                    ->where('c.isHidden = :isHidden')
                    ->andWhere('a.status = :status')
                    ->setParameter('isHidden', false)
                    ->setParameter('status', ArticleStatus::PUBLISHED->value)
                    ->orderBy('c.createdAt', 'DESC')
                    ->setMaxResults($count);

                return $qb->getQuery()->getResult();
            },
        );
    }
}
