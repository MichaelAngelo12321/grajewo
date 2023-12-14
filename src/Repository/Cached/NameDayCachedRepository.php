<?php

declare(strict_types=1);

namespace App\Repository\Cached;

use App\Repository\NameDayRepository;
use DateTime;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class NameDayCachedRepository
{
    public function __construct(
        private CacheInterface $cache,
        private NameDayRepository $nameDayRepository,
    ) {
    }

    public function findToday(): ?string
    {
        $cacheKey = CacheKeyPrefix::NAME_DAY_TODAY;

        return $this->cache->get($cacheKey, function (ItemInterface $item) {
            $item->expiresAt(new DateTime('today 23:59:59'));

            $nameDay = $this->nameDayRepository->findOneBy([
                'day' => date('d'),
                'month' => date('m'),
            ]);

            return $nameDay
                ? implode(', ', explode(' ', $nameDay->getNames()))
                : null;
        });
    }
}
