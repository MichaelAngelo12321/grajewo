<?php

declare(strict_types=1);

namespace App\Repository\Cached;

use App\Repository\AdvertisementRepository;
use DateInterval;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class AdvertisementCachedRepository
{
    public function __construct(
        private CacheInterface $cache,
        private AdvertisementRepository $advertisementRepository,
    ) {
    }

    public function findPromotedAdvertisements(int $limit = 4): array
    {
        return $this->cache->get(CacheKeyPrefix::ADVERTISEMENT_PROMOTED . $limit, function (ItemInterface $item) use ($limit) {
            $item->expiresAfter(DateInterval::createFromDateString('24 hour'));

            return $this->advertisementRepository->findPromotedAdvertisements($limit);
        });
    }

    public function findLatestAdvertisements(int $limit = 4): array
    {
        return $this->cache->get(CacheKeyPrefix::ADVERTISEMENT_LATEST . $limit, function (ItemInterface $item) use ($limit) {
            $item->expiresAfter(DateInterval::createFromDateString('1 hour'));

            return $this->advertisementRepository->findLatestAdvertisements($limit);
        });
    }
}
