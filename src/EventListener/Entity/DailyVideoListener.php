<?php

declare(strict_types=1);

namespace App\EventListener\Entity;

use App\Entity\DailyVideo;
use App\Repository\Cached\CacheKeyPrefix;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Contracts\Cache\CacheInterface;

#[AsEntityListener(event: Events::postPersist, method: 'clearCache', entity: DailyVideo::class)]
#[AsEntityListener(event: Events::postRemove, method: 'clearCache', entity: DailyVideo::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'clearCache', entity: DailyVideo::class)]
class DailyVideoListener
{
    public function __construct(
        private CacheInterface $cache,
    ) {
    }

    public function clearCache(): void
    {
        $this->cache->clear(CacheKeyPrefix::LATEST_DAILY_VIDEO);
    }
}
