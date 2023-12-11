<?php

declare(strict_types=1);

namespace App\Repository\Cached;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use DateInterval;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CategoryCachedRepository
{
    public function __construct(
        private CategoryRepository $categoryRepository,
        private CacheInterface $cache,
    )
    {
    }

    public function findAll(): array
    {
        return $this->cache->get(CacheKeyPrefix::CATEGORY_ALL, function (ItemInterface $item) {
            $item->expiresAfter(new DateInterval('P1Y'));

            return $this->categoryRepository->findBy([], ['positionOrder' => 'ASC']);
        });
    }

    public function findTopCategory(): Category
    {
        return $this->cache->get(CacheKeyPrefix::CATEGORY_TOP, function (ItemInterface $item) {
            $item->expiresAfter(new DateInterval('P1Y'));

            return $this->categoryRepository->findOneBy(['positionOrder' => 0, 'isRoot' => false]);
        });
    }
}
