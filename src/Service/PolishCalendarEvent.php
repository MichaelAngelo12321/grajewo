<?php

declare(strict_types=1);

namespace App\Service;

use DateTimeImmutable;

class PolishCalendarEvent
{
    private const HOLIDAYS = [
        '01-01' => 'Nowy Rok',
        '01-06' => 'Trzech Króli',
        'easter' => 'Wielkanoc',
        'easter+1' => 'Poniedziałek Wielkanocny',
        '05-01' => 'Święto Pracy',
        '05-03' => 'Święto Konstytucji 3 Maja',
        'easter+49' => 'Zielone Świątki',
        'easter+60' => 'Boże Ciało',
        '08-15' => 'Wniebowzięcie Najświętszej Maryi Panny',
        '11-01' => 'Wszystkich Świętych',
        '11-11' => 'Święto Niepodległości',
        '12-25' => 'Boże Narodzenie (pierwszy dzień)',
        '12-26' => 'Boże Narodzenie (drugi dzień)',
    ];

    public function getHolidays(): array
    {
        $easter = $this->getEasterDate();
        $holidays = [];

        foreach (self::HOLIDAYS as $key => $value) {
            if ($key === 'easter') {
                $holidays[date('m-d', $easter->getTimestamp())] = $value;
            } elseif (str_contains($key, 'easter')) {
                $key = str_replace('easter', '', $key);
                $holidays[$easter->modify($key . ' day')->format('m-d')] = $value;
            } else {
                $holidays[$key] = $value;
            }
        }

        return $holidays;
    }

    private function getEasterDate(): DateTimeImmutable
    {
        $year = (int)date('Y');

        $golden = $year % 19 + 1;

        $dom = ($year + (int)($year / 4) - (int)($year / 100) + (int)($year / 400)) % 7;
        if ($dom < 0) {
            $dom += 7;
        }

        $solar = (int)(($year - 1600) / 100) - (int)(($year - 1600) / 400);

        $lunar = (int)((int)(($year - 1400) / 100) * 8) / 25;

        $pfm = (3 - 11 * $golden + $solar - $lunar) % 30;
        if ($pfm < 0) {
            $pfm += 30;
        }

        if ($pfm === 29 || $pfm === 28 && $golden > 11) {
            $pfm--;
        }

        $tmp = (4 - $pfm - $dom) % 7;
        if ($tmp < 0) {
            $tmp += 7;
        }

        $easter = $pfm + $tmp + 1;

        return new DateTimeImmutable(
            date('Y-m-d', mktime(0, 0, 0, 3, 21 + $easter, $year)),
        );
    }
}
