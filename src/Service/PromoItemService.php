<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\PromoItem;
use App\Repository\PromoItemRepository;
use Doctrine\DBAL\Exception as DBALException;
use Psr\Log\LoggerInterface;

class PromoItemService
{
    public function __construct(
        private readonly PromoItemRepository $promoItemRepository,
        private readonly LoggerInterface $logger,
        private array $availablePromoItems = [],
        private array $displayedPromoItems = [],
    ) {
    }

    public function updateViewsCounters(): void
    {
        $this->countViews(array_keys($this->displayedPromoItems));
    }

    /**
     * View statistics must never take the page down, so a database error here
     * (deadlock, lock wait timeout) is logged and swallowed.
     *
     * @param int[] $ids
     */
    public function countViews(array $ids): void
    {
        if ($ids === []) {
            return;
        }

        try {
            $this->promoItemRepository->incrementViews($ids);
        } catch (DBALException $exception) {
            $this->logger->warning('Could not update promo item view counters', [
                'ids' => $ids,
                'exception' => $exception,
            ]);
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
            $item = $this->availablePromoItems[$slot];
            $this->displayedPromoItems[$item->getId()] = $item;

            return $item;
        }

        return null;
    }
}
