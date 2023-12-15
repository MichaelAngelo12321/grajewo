<?php

declare(strict_types=1);

namespace App\EventListener\Entity;

use App\Entity\Article;
use App\Repository\Cached\CacheKeyPrefix;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Contracts\Cache\CacheInterface;

#[AsEntityListener(event: Events::postPersist, method: 'clearCache', entity: Article::class)]
#[AsEntityListener(event: Events::postRemove, method: 'clearCache', entity: Article::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'clearCache', entity: Article::class)]
class ArticleListener
{
    public function __construct(
        private CacheInterface $cache,
    ) {
    }

    public function clearCache(): void
    {
        $this->cache->clear(CacheKeyPrefix::ARTICLE_LATEST_FROM_CATEGORY);
        $this->cache->clear(CacheKeyPrefix::ARTICLE_MOST_POPULAR);
    }
}
