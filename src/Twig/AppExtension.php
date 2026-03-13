<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\PromoItem;
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
use App\Service\PromoItemService;
use DateTime;
use DateTimeImmutable;
use Twig\Environment;
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
        private PromoItemService $promoItemService,
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
            new TwigFilter('inject_ads', [$this, 'injectAds'], ['needs_environment' => true, 'is_safe' => ['html']]),
        ];
    }

    public function injectAds(Environment $env, ?string $content): string
    {
        if (empty($content)) {
            return '';
        }

        $paragraphs = explode('</p>', $content);
        $paragraphs = array_filter($paragraphs, function ($p) {
            return trim($p) !== '';
        });
        $paragraphs = array_values($paragraphs);
        $count = count($paragraphs);

        // Render ads
        $adLeftHtml = '';
        $promoItemLeft = $this->getPromoItem('ARTYKUL_LEWA');
        if ($promoItemLeft) {
            $adLeftHtml = $env->render('app/widgets/promo_space.html.twig', [
                'slot' => 'ARTYKUL_LEWA'
            ]);
            
            if (!empty(trim($adLeftHtml))) {
                // Wrapper for float left (desktop) / centered (mobile)
                $adLeftHtml = '<div class="float-md-start me-md-4 mb-3 text-center" style="max-width: 300px;">' . $adLeftHtml . '</div>';
            }
        }

        $adMiddleHtml = '';
        if ($count >= 2) {
            $promoItemMiddle = $this->getPromoItem('ARTYKUL_SRODEK');
            if ($promoItemMiddle) {
                $adMiddleHtml = $env->render('app/widgets/promo_space.html.twig', [
                    'slot' => 'ARTYKUL_SRODEK'
                ]);

                if (!empty(trim($adMiddleHtml))) {
                    // Wrapper for center block
                    $adMiddleHtml = '<div class="d-flex justify-content-center my-4 w-100">' . $adMiddleHtml . '</div>';
                }
            }
        }

        // Inject LEWA before first paragraph content
        if (!empty($adLeftHtml) && isset($paragraphs[0])) {
            $paragraphs[0] = $adLeftHtml . $paragraphs[0];
        }

        // Inject SRODEK after middle paragraph
        // Use ($count - 1) / 2 to ensure it's strictly in the middle or slightly above
        $middleIndex = (int) floor(($count - 1) / 2);
        
        $newContent = '';
        foreach ($paragraphs as $index => $p) {
            $newContent .= $p . '</p>';
            
            // Insert middle ad after the middle paragraph
            if (!empty($adMiddleHtml) && $index === $middleIndex) {
                $newContent .= $adMiddleHtml;
            }
        }

        return $newContent;
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
            new TwigFunction('get_gas_station_fuel_types', [$this, 'getGasStationFuelTypes']),
            new TwigFunction('get_latest_daily_image', [$this->userContentCachedRepository, 'findLatestDailyImage']),
            new TwigFunction('get_latest_daily_video', [$this->userContentCachedRepository, 'findLatestDailyVideo']),
            new TwigFunction('get_latest_user_reports', [$this->userReportCachedRepository, 'findLatest']),
            new TwigFunction('get_promo_item', [$this, 'getPromoItem']),
            new TwigFunction('get_setting', [$this->settingCachedRepository, 'get']),
            new TwigFunction('get_today_pharmacy_duty', [$this->pharmacyDutyCachedRepository, 'getTodayPharmacyDuty']),
            new TwigFunction('update_promo_item_view_counters', [$this, 'updatePromoItemViewCounters']),
        ];
    }

    public function getGasStationFuelTypes(): array
    {
        return [
            'unleaded' => '95',
            'superUnleaded' => '98',
            'diesel' => 'ON',
            'liquidGas' => 'LPG'
        ];
    }

    public function getPromoItem(string $slot): ?PromoItem
    {
        return $this->promoItemService->findBySlot($slot);
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
            return $dateTime->format('d.m.Y H:i');
        }
    }

    public function pregReplace($string, $regex = '', $replace = ''): string
    {
        return preg_replace($regex, $replace, $string);
    }

    public function updatePromoItemViewCounters(): void
    {
        $this->promoItemService->updateViewsCounters();
    }
}
