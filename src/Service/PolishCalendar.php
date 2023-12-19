<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\Cached\ArticleCachedRepository;
use DateTimeImmutable;

class PolishCalendar
{
    public function __construct(
        private ArticleCachedRepository $articleRepository,
        private PolishCalendarEvent $polishCalendarEvent,
    ) {
    }

    public function getForYearMonth(int $year, int $month): array
    {
        $holidays = $this->polishCalendarEvent->getHolidays();
        $date = new DateTimeImmutable(sprintf('%d-%d-01', $year, $month));
        $daysInMonth = (int)$date->format('t');
        $thisMonthEvents = $this->articleRepository->findEventsFromThisMonth();
        $today = (int)(new DateTimeImmutable())->format('d');
        $calendar = [];

        for ($day = 1; $day <= $daysInMonth; ++$day) {
            $calendar[$day] = [
                'day' => $day,
                'date' => $date->format('Y-m-d'),
                'isHoliday' => array_key_exists($date->format('m-d'), $holidays),
                'holidayName' => $holidays[$date->format('m-d')] ?? null,
                'title' => [],
            ];

            if (isset($thisMonthEvents[$day])) {
                $calendar[$day]['hasEvents'] = true;
                $calendar[$day]['events'] = $thisMonthEvents[$day];
            } else {
                $calendar[$day]['hasEvents'] = false;
            }

            if ($day === $today) {
                $calendar[$day]['isToday'] = true;
            } else {
                $calendar[$day]['isToday'] = false;
            }

            if ($calendar[$day]['isToday']) {
                $calendar[$day]['title'][] = 'Dzisiaj';
            }

            if ($calendar[$day]['isHoliday']) {
                $calendar[$day]['title'][] = $calendar[$day]['holidayName'];
            }

            if ($calendar[$day]['hasEvents']) {
                $eventsCount = count($calendar[$day]['events']);
                $calendar[$day]['title'][] = sprintf('%d %s', $eventsCount, $this->pluralizeEventsNumber($eventsCount));
            }

            $calendar[$day]['title'] = implode(', ', $calendar[$day]['title']);

            $date = $date->modify('+1 day');
        }

        return $calendar;
    }

    private function pluralizeEventsNumber(int $number): string
    {
        if ($number === 1) {
            return 'wydarzenie';
        } elseif ($number >= 2 && $number <= 4) {
            return 'wydarzenia';
        } else {
            return 'wydarzeń';
        }
    }
}
