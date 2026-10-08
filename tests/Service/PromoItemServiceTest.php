<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\PromoItem;
use App\Repository\PromoItemRepository;
use App\Service\PromoItemService;
use Doctrine\DBAL\Exception\DeadlockException;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class PromoItemServiceTest extends TestCase
{
    private function createItem(int $id, string $position): PromoItem
    {
        $item = (new PromoItem())->setPosition($position);
        (new \ReflectionProperty(PromoItem::class, 'id'))->setValue($item, $id);

        return $item;
    }

    public function testCountsViewsOfDisplayedItemsWithOneAtomicUpdate(): void
    {
        $repository = $this->createMock(PromoItemRepository::class);
        $repository->method('findAllActive')->willReturn([
            $this->createItem(7, 'PRAWY_1'),
            $this->createItem(3, 'LEWA_1'),
        ]);
        $repository->expects(self::once())->method('incrementViews')->with(self::callback(function (array $ids): bool {
            sort($ids);

            return $ids === [3, 7];
        }));

        $service = new PromoItemService($repository, new NullLogger());
        $service->findBySlot('PRAWY_1');
        $service->findBySlot('LEWA_1');
        $service->findBySlot('LEWA_1');
        $service->updateViewsCounters();
    }

    public function testDatabaseErrorWhileCountingViewsDoesNotBreakThePage(): void
    {
        $repository = $this->createMock(PromoItemRepository::class);
        $repository->method('findAllActive')->willReturn([$this->createItem(3, 'LEWA_1')]);
        $repository->method('incrementViews')->willThrowException($this->createMock(DeadlockException::class));

        $service = new PromoItemService($repository, new NullLogger());
        $service->findBySlot('LEWA_1');
        $service->updateViewsCounters();

        $this->addToAssertionCount(1); // reaching this line means the exception was contained
    }

    public function testNothingIsWrittenWhenNoItemWasDisplayed(): void
    {
        $repository = $this->createMock(PromoItemRepository::class);
        $repository->expects(self::never())->method('incrementViews');

        (new PromoItemService($repository, new NullLogger()))->updateViewsCounters();
    }
}
