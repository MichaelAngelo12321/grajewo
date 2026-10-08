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

    public function getHolidays(?int $year = null): array
    {
        $easter = $this->getEasterDate($year ?? (int) date('Y'));
        $holidays = [];

        foreach (self::HOLIDAYS as $key => $value) {
            if ($key === 'easter') {
                $holidays[$easter->format('m-d')] = $value;
            } elseif (str_contains($key, 'easter')) {
                $offset = str_replace('easter', '', $key);
                $holidays[$easter->modify($offset . ' day')->format('m-d')] = $value;
            } else {
                $holidays[$key] = $value;
            }
        }

        return $holidays;
    }

    private function getEasterDate(int $year): DateTimeImmutable
    {
        $golden = $year % 19 + 1;

        $dom = ($year + intdiv($year, 4) - intdiv($year, 100) + intdiv($year, 400)) % 7;
        if ($dom < 0) {
            $dom += 7;
        }

        $solar = intdiv($year - 1600, 100) - intdiv($year - 1600, 400);

        // Integer division is required: `/ 25` without intdiv() leaves a float and
        // PHP 8.1+ deprecates using that float as a modulo operand (homepage 500).
        $lunar = intdiv(intdiv($year - 1400, 100) * 8, 25);

        $pfm = (3 - 11 * $golden + $solar - $lunar) % 30;
        if ($pfm < 0) {
            $pfm += 30;
        }

        if ($pfm === 29 || ($pfm === 28 && $golden > 11)) {
            $pfm--;
        }

        $tmp = (4 - $pfm - $dom) % 7;
        if ($tmp < 0) {
            $tmp += 7;
        }

        $easterDaysAfterMarch21 = $pfm + $tmp + 1;

        return (new DateTimeImmutable(sprintf('%d-03-21', $year)))
            ->modify(sprintf('+%d days', $easterDaysAfterMarch21));
    }
}
