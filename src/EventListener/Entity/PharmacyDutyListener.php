<?php

declare(strict_types=1);

namespace App\EventListener\Entity;

use App\Entity\PharmacyDuty;
use App\Repository\Cached\CacheKeyPrefix;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Contracts\Cache\CacheInterface;

#[AsEntityListener(event: Events::postPersist, method: 'clearCache', entity: PharmacyDuty::class)]
#[AsEntityListener(event: Events::postRemove, method: 'clearCache', entity: PharmacyDuty::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'clearCache', entity: PharmacyDuty::class)]
class PharmacyDutyListener
{
    public function __construct(
        private CacheInterface $cache,
    ) {
    }

    public function clearCache(): void
    {
        $this->cache->clear(CacheKeyPrefix::PHARMACY_DUTY_TODAY);
    }
}
