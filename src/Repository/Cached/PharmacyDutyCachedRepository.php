<?php

declare(strict_types=1);

namespace App\Repository\Cached;

use App\Entity\PharmacyDuty;
use App\Repository\PharmacyDutyRepository;
use DateTime;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class PharmacyDutyCachedRepository
{
    public function __construct(
        private CacheInterface $cache,
        private PharmacyDutyRepository $pharmacyDutyRepository,
    ) {
    }

    public function getTodayPharmacyDuty(): ?PharmacyDuty
    {
        return $this->cache->get(CacheKeyPrefix::PHARMACY_DUTY_TODAY, function (ItemInterface $item) {
            $item->expiresAt(new DateTime('tomorrow 00:00:00'));

            return $this->pharmacyDutyRepository->findOneBy(['day' => (int) date('N') - 1]);
        });
    }
}
