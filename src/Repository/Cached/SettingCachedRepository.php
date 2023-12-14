<?php

declare(strict_types=1);

namespace App\Repository\Cached;

use App\Repository\SettingRepository;
use Symfony\Contracts\Cache\CacheInterface;

class SettingCachedRepository
{
    public function __construct(
        private CacheInterface $cache,
        private SettingRepository $settingRepository,
    ) {
    }

    public function get(string $name): ?string
    {
        return $this->cache->get(CacheKeyPrefix::SETTING . $name, function () use ($name) {
            return $this->settingRepository->findOneBy(['name' => $name])?->getValue();
        });
    }
}
