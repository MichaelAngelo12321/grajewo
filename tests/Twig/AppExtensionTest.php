<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use App\Repository\Cached\ArticleCommentCachedRepository;
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
use App\Twig\AppExtension;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

class AppExtensionTest extends TestCase
{
    private function createExtension(): AppExtension
    {
        return new AppExtension(
            $this->createMock(AirPollutionRepository::class),
            $this->createMock(ArticleCommentCachedRepository::class),
            $this->createMock(CurrencyRateRepository::class),
            $this->createMock(GasStationCachedRepository::class),
            $this->createMock(NameDayCachedRepository::class),
            $this->createMock(PharmacyDutyCachedRepository::class),
            $this->createMock(PolishCalendar::class),
            $this->createMock(PromoItemService::class),
            $this->createMock(SettingCachedRepository::class),
            $this->createMock(UserContentCachedRepository::class),
            $this->createMock(UserReportCachedRepository::class),
            $this->createMock(WeatherRepository::class),
        );
    }

    public function testInjectAdsKeepsBlankParagraphInsertedAsDoubleEnterInTinyMce(): void
    {
        $extension = $this->createExtension();
        $env = $this->createMock(Environment::class);

        // TinyMCE emits <p>&nbsp;</p> when the author presses Enter twice to
        // add a blank line between paragraphs.
        $content = '<p>Akapit pierwszy.</p><p>&nbsp;</p><p>Akapit drugi.</p>';

        $result = $extension->injectAds($env, $content);

        $this->assertSame($content, $result);
    }
}
