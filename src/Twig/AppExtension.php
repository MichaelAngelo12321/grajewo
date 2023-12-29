<?php

declare(strict_types=1);

namespace App\Twig;

use App\Enum\ArticleStatus;
use App\Repository\Cached\GasStationCachedRepository;
use App\Repository\Cached\NameDayCachedRepository;
use App\Repository\Cached\PharmacyDutyCachedRepository;
use App\Repository\Cached\SettingCachedRepository;
use App\Repository\Cached\UserContentCachedRepository;
use App\Repository\Cached\UserReportCachedRepository;
use App\Repository\External\AirPollutionRepository;
use App\Repository\External\CurrencyRateRepository;
use App\Repository\External\WeatherRepository;
use App\Service\PolishCalendar;
use DateTime;
use DateTimeImmutable;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(
        private AirPollutionRepository $airPollutionRepository,
        private CurrencyRateRepository $currencyRateRepository,
        private GasStationCachedRepository $gasStationCachedRepository,
        private NameDayCachedRepository $nameDayCachedRepository,
        private PharmacyDutyCachedRepository $pharmacyDutyCachedRepository,
        private PolishCalendar $polishCalendar,
        private SettingCachedRepository $settingCachedRepository,
        private UserContentCachedRepository $userContentCachedRepository,
        private UserReportCachedRepository $userReportCachedRepository,
        private WeatherRepository $weatherRepository,
    ) {
    }

    public function articleStatusColor(ArticleStatus $status): string
    {
        return match ($status) {
            ArticleStatus::DRAFT => 'secondary',
            ArticleStatus::PUBLISHED => 'success',
            ArticleStatus::HIDDEN => 'warning',
            ArticleStatus::ARCHIVED => 'danger',
        };
    }

    public function articleStatusName(ArticleStatus $status): string
    {
        return match ($status) {
            ArticleStatus::DRAFT => 'Szkic',
            ArticleStatus::PUBLISHED => 'Opublikowany',
            ArticleStatus::HIDDEN => 'Ukryty',
            ArticleStatus::ARCHIVED => 'Zarchiwizowany',
        };
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('article_status_color', [$this, 'articleStatusColor']),
            new TwigFilter('article_status_name', [$this, 'articleStatusName']),
            new TwigFilter('preg_replace', [$this, 'pregReplace']),
            new TwigFilter('relative_datetime', [$this, 'getRelativeDateTime']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_month_calendar', [$this->polishCalendar, 'getForYearMonth']),
            new TwigFunction('get_currency_rate', [$this->currencyRateRepository, 'getRate']),
            new TwigFunction('get_current_air_quality_level', [$this->airPollutionRepository, 'getAirQuality']),
            new TwigFunction('get_current_day_names', [$this->nameDayCachedRepository, 'findToday']),
            new TwigFunction('get_current_weather', [$this->weatherRepository, 'getWeather']),
            new TwigFunction('get_gas_stations', [$this->gasStationCachedRepository, 'findStationsWithPrices']),
            new TwigFunction('get_latest_daily_image', [$this->userContentCachedRepository, 'findLatestDailyImage']),
            new TwigFunction('get_latest_daily_video', [$this->userContentCachedRepository, 'findLatestDailyVideo']),
            new TwigFunction('get_latest_user_reports', [$this->userReportCachedRepository, 'findLatest']),
            new TwigFunction('get_setting', [$this->settingCachedRepository, 'get']),
            new TwigFunction('get_today_pharmacy_duty', [$this->pharmacyDutyCachedRepository, 'getTodayPharmacyDuty']),
        ];
    }

    public function getRelativeDateTime(DateTimeImmutable $dateTime): string
    {
        $now = new DateTime();
        $diff = $now->diff($dateTime);

        if ($diff->d === 0) {
            return 'dzisiaj, ' . $dateTime->format('H:i');
        } elseif ($diff->d === 1) {
            return 'wczoraj, ' . $dateTime->format('H:i');
        } else {
            return $dateTime->format('Y-m-d');
        }
    }

    public function pregReplace($string, $regex = '', $replace = ''): string
    {
        return preg_replace($regex, $replace, $string);
    }
}
