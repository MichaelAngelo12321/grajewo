<?php

declare(strict_types=1);

namespace App\Repository\Cached;

use App\Repository\CompanyRepository;
use DateInterval;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CompanyCachedRepository
{
    public function __construct(
        private CacheInterface $cache,
        private CompanyRepository $companyRepository,
    ) {
    }

    public function findPromotedCompanies(int $limit = 6): array
    {
        return $this->cache->get(CacheKeyPrefix::COMPANY_PROMOTED . $limit, function (ItemInterface $item) use ($limit) {
            $item->expiresAfter(DateInterval::createFromDateString('24 hour'));

            return $this->companyRepository->findBy(['isActive' => true, 'isPromoted' => true], ['name' => 'ASC'], $limit);
        });
    }
}
