<?php

declare(strict_types=1);

namespace App\Repository\Cached;

use App\Repository\UserReportRepository;
use DateInterval;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class UserReportCachedRepository
{
    public function __construct(
        private CacheInterface $cache,
        private UserReportRepository $userReportRepository,
    ) {
    }

    public function findLatest(int $count = 5): array
    {
        return $this->cache->get(
            CacheKeyPrefix::USER_REPORT_LAST . $count,
            function (ItemInterface $item) use ($count) {
                $item->expiresAfter(DateInterval::createFromDateString('24 hour'));

                return $this->userReportRepository->findBy([], ['createdAt' => 'DESC'], $count);
            },
        );
    }
}
