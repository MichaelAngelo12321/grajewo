<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\PromoItem;
use App\Repository\PromoItemRepository;

class PromoItemService
{
    public function __construct(
        private readonly PromoItemRepository $promoItemRepository
    ) {
    }

    public function findBySlot(string $slot): ?PromoItem
    {
        $promoItem = $this->promoItemRepository->findBySlot($slot);

        if ($promoItem !== null) {
            $this->promoItemRepository->incrementViews($promoItem);
        }

        return $promoItem;
    }
}
