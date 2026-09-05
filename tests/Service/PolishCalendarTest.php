<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Article;
use App\Repository\Cached\ArticleCachedRepository;
use App\Service\PolishCalendar;
use App\Service\PolishCalendarEvent;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class PolishCalendarTest extends TestCase
{
    private function createCalendar(array $events): PolishCalendar
    {
        $repository = $this->createMock(ArticleCachedRepository::class);
        $repository->method('findEventsFromThisMonth')->willReturn($events);

        return new PolishCalendar($repository, new PolishCalendarEvent());
    }

    private function createEvent(string $dateTime): Article
    {
        return (new Article())->setIsEvent(true)->setEventDateTime(new DateTimeImmutable($dateTime));
    }

    public function testMarksDaysWithEventsIncludingSingleDigitDays(): void
    {
        $now = new DateTimeImmutable();
        $month = $now->format('Y-m');

        $calendar = $this->createCalendar([
            $this->createEvent($month . '-05 12:00:00'),
            $this->createEvent($month . '-15 18:00:00'),
            $this->createEvent($month . '-15 20:00:00'),
        ])->getForYearMonth((int) $now->format('Y'), (int) $now->format('m'));

        self::assertTrue($calendar[5]['hasEvents'], 'Day 5 should have an event');
        self::assertTrue($calendar[15]['hasEvents'], 'Day 15 should have events');
        self::assertCount(2, $calendar[15]['events']);
        self::assertFalse($calendar[1]['hasEvents']);
        self::assertFalse($calendar[2]['hasEvents']);
        self::assertStringEndsWith('1 wydarzenie', $calendar[5]['title']); // may be prefixed with "Dzisiaj, "
        self::assertStringEndsWith('2 wydarzenia', $calendar[15]['title']);
    }

    public function testDayWithoutEventsIsNotMarked(): void
    {
        $now = new DateTimeImmutable();
        $calendar = $this->createCalendar([])->getForYearMonth((int) $now->format('Y'), (int) $now->format('m'));

        foreach ($calendar as $day) {
            self::assertFalse($day['hasEvents']);
        }
    }
}
