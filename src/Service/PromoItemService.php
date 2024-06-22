<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\PromoItem;
use App\Repository\PromoItemRepository;

class PromoItemService
{
    public function __construct(
        private readonly PromoItemRepository $promoItemRepository,
        private array $availablePromoItems = [],
        private array $displayedPromoItems = [],
    ) {
    }

    public function updateViewsCounters(): void
    {
        if (!empty($this->displayedPromoItems)) {
            $this->promoItemRepository->increaseBatchViews($this->displayedPromoItems);
        }
    }

    public function findBySlot(string $slot): ?PromoItem
    {
        if (empty($this->availablePromoItems)) {
            $promoItems = $this->promoItemRepository->findAllActive();

            /** @var PromoItem $item */
            foreach ($promoItems as $item) {
                if (!isset($this->availablePromoItems[$item->getPosition()])) {
                    $this->availablePromoItems[$item->getPosition()] = $item;
                }
            }
        }

        if (isset($this->availablePromoItems[$slot])) {
            $this->displayedPromoItems[] = $this->availablePromoItems[$slot];

            return $this->availablePromoItems[$slot];
        }

        return null;
    }
}
