<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Category;
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
        return $this->cache->get('categories', function (ItemInterface $item) {
            $item->expiresAfter(new DateInterval('P1Y'));

            return $this->categoryRepository->findAll();
        });
    }

    public function findTopCategory(): Category
    {
        return $this->cache->get('top_category', function (ItemInterface $item) {
            $item->expiresAfter(new DateInterval('P1Y'));

            return $this->categoryRepository->findOneBy(['isTop' => true]);
        });
    }
}
