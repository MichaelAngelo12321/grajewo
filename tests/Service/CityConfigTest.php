<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\CityConfig;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CityConfigTest extends TestCase
{
    public function testUnknownCityThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown APP_CITY "warszawa"');

        new CityConfig('warszawa');
    }

    public function testKeyIsCaseAndWhitespaceInsensitive(): void
    {
        self::assertSame('grajewo', (new CityConfig(' Grajewo '))->getKey());
    }

    /** @dataProvider cityProvider */
    public function testEveryCityIsComplete(string $key, string $domain, string $logoKey): void
    {
        $city = new CityConfig($key);

        self::assertSame($domain, $city->getDomain());
        self::assertSame($logoKey, $city->getLogoKey());
        self::assertStringEndsWith('@' . $domain, $city->getEmail());
        self::assertStringStartsWith('https://www.facebook.com/', $city->getFacebook());
        self::assertSame(['Urzędy', 'Instytucje', 'Miejskie serwisy'], array_keys($city->getInstitutions()));

        foreach ($city->getInstitutions() as $links) {
            self::assertNotEmpty($links);
            foreach ($links as $link) {
                self::assertNotSame('', $link['name']);
                self::assertMatchesRegularExpression('#^https?://#', $link['url']);
            }
        }
    }

    public static function cityProvider(): iterable
    {
        yield 'augustow' => ['augustow', 'augustow24.pl', 'a24'];
        yield 'elk' => ['elk', 'elk24.pl', 'e24'];
        yield 'grajewo' => ['grajewo', 'grajewo24.pl', 'g24'];
    }

    public function testPartnersExcludeCurrentCity(): void
    {
        $partners = (new CityConfig('grajewo'))->getPartners();

        self::assertSame(['augustow', 'elk'], array_column($partners, 'key'));
        self::assertSame(['a24', 'e24'], array_column($partners, 'logoKey'));
        self::assertSame(['augustow24.pl', 'elk24.pl'], array_column($partners, 'domain'));
    }

    public function testLogoAndShareImageFilesExist(): void
    {
        $imagesDir = __DIR__ . '/../../assets/images/';

        foreach (CityConfig::getAvailableKeys() as $key) {
            $city = new CityConfig($key);
            $logo = $city->getLogoKey();

            foreach (["logo_{$logo}_light.svg", "logo_{$logo}_dark.svg", "logo_{$logo}_light.jpeg", $city->getShareImage()] as $file) {
                self::assertFileExists($imagesDir . $file, "Missing image for city {$key}");
            }
        }
    }
}
