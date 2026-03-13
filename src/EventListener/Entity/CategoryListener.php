<?php

declare(strict_types=1);

namespace App\EventListener\Entity;

use App\Entity\Category;
use App\Repository\Cached\CacheKeyPrefix;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Contracts\Cache\CacheInterface;

#[AsEntityListener(event: Events::postPersist, method: 'clearCache', entity: Category::class)]
#[AsEntityListener(event: Events::postRemove, method: 'clearCache', entity: Category::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'clearCache', entity: Category::class)]
class CategoryListener
{
    public function __construct(
        private CacheInterface $cache,
    ) {
    }

    public function clearCache(): void
    {
        $this->cache->delete(CacheKeyPrefix::CATEGORY_ALL);
        $this->cache->delete(CacheKeyPrefix::CATEGORY_TOP);
        $this->cache->delete(CacheKeyPrefix::ARTICLE_LATEST_FROM_CATEGORY);
    }
}
