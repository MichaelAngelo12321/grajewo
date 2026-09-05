<?php

declare(strict_types=1);

namespace App\Service;

use InvalidArgumentException;

/**
 * Per-city branding and links, selected by the APP_CITY environment variable.
 *
 * Everything that differs between Augustów, Ełk and Grajewo (logos, domain,
 * contact e-mail, Facebook page, footer institutions) lives here, so the same
 * codebase serves every city and each server only sets APP_CITY.
 */
final class CityConfig
{
    private const CITIES = [
        'augustow' => [
            'name' => 'Augustów',
            'domain' => 'augustow24.pl',
            'logoKey' => 'a24',
            'shareImage' => 'default-share-image-a24.jpg',
            'email' => 'redakcja@augustow24.pl',
            'facebook' => 'https://www.facebook.com/Augustow24',
            'institutions' => [
                'Urzędy' => [
                    ['name' => 'Urząd Miejski', 'url' => 'https://urzad.augustow.pl/'],
                    ['name' => 'Urząd Gminy', 'url' => 'https://samorzad.gov.pl/web/gmina-augustow'],
                    ['name' => 'Powiatowy Urząd Pracy', 'url' => 'https://augustow.praca.gov.pl/'],
                    ['name' => 'Starostwo Powiatowe', 'url' => 'https://powiat-augustowski.eu/'],
                ],
                'Instytucje' => [
                    ['name' => 'Policja', 'url' => 'https://augustow.policja.gov.pl/'],
                    ['name' => 'Straż Pożarna', 'url' => 'https://www.gov.pl/web/kppsp-augustow'],
                    ['name' => 'Augustowskie Centrum Edukacyjne', 'url' => 'https://www.acedu.pl/'],
                    ['name' => 'I LO im. G. Piramowicza', 'url' => 'https://piramowicz.pl/'],
                    ['name' => 'MOPS w Augustowie', 'url' => 'https://mops.augustow.pl/'],
                ],
                'Miejskie serwisy' => [
                    ['name' => 'Augustowskie Placówki Kultury', 'url' => 'https://www.apk.augustow.pl/'],
                    ['name' => 'Muzeum Ziemi Augustowskiej', 'url' => 'https://www.apk.augustow.pl/muzeum'],
                    ['name' => 'Miejska Biblioteka', 'url' => 'https://biblioteka.augustow.pl/'],
                ],
            ],
        ],
        'elk' => [
            'name' => 'Ełk',
            'domain' => 'elk24.pl',
            'logoKey' => 'e24',
            'shareImage' => 'default-share-image-e24.jpg',
            'email' => 'redakcja@elk24.pl',
            'facebook' => 'https://www.facebook.com/Elk24pl',
            'institutions' => [
                'Urzędy' => [
                    ['name' => 'Urząd Miasta', 'url' => 'https://www.elk.pl/'],
                    ['name' => 'Urząd Gminy', 'url' => 'https://elk.gmina.pl/'],
                    ['name' => 'Powiatowy Urząd Pracy', 'url' => 'https://elk.praca.gov.pl/'],
                    ['name' => 'Starostwo Powiatowe', 'url' => 'https://powiat.elk.pl/'],
                ],
                'Instytucje' => [
                    ['name' => 'Policja', 'url' => 'https://elk.policja.gov.pl/'],
                    ['name' => 'Straż Pożarna', 'url' => 'https://www.gov.pl/web/kppsp-elk'],
                    ['name' => 'Zespół Szkół nr 1', 'url' => 'https://www.zs1.elk.pl/'],
                    ['name' => 'Zespół Szkół nr 2', 'url' => 'https://zs2.elk.pl/'],
                    ['name' => 'MOPS w Ełku', 'url' => 'https://mopselk.naszops.pl/'],
                ],
                'Miejskie serwisy' => [
                    ['name' => 'Ełckie Centrum Kultury', 'url' => 'https://eck.elk.pl/'],
                    ['name' => 'Muzeum Historyczne w Ełku', 'url' => 'https://muzeum.elk.pl/'],
                    ['name' => 'Miejska Biblioteka', 'url' => 'https://biblioteka.elk.pl/'],
                ],
            ],
        ],
        'grajewo' => [
            'name' => 'Grajewo',
            'domain' => 'grajewo24.pl',
            'logoKey' => 'g24',
            'shareImage' => 'default-share-image.jpg',
            'email' => 'redakcja@grajewo24.pl',
            'facebook' => 'https://www.facebook.com/Grajewo24',
            'institutions' => [
                'Urzędy' => [
                    ['name' => 'Urząd Miasta', 'url' => 'https://www.grajewo.pl/'],
                    ['name' => 'Urząd Gminy', 'url' => 'https://samorzad.gov.pl/web/gmina-grajewo'],
                    ['name' => 'Powiatowy Urząd Pracy', 'url' => 'https://grajewo.praca.gov.pl/'],
                    ['name' => 'Starostwo Powiatowe', 'url' => 'https://www.starostwograjewo.pl/'],
                ],
                'Instytucje' => [
                    ['name' => 'Policja', 'url' => 'https://grajewo.policja.gov.pl/'],
                    ['name' => 'Straż Pożarna', 'url' => 'https://www.gov.pl/web/kppsp-grajewo'],
                    ['name' => 'Zespół Szkół nr 1', 'url' => 'https://zs1kopernik.szkolnastrona.pl/'],
                    ['name' => 'Zespół Szkół nr 2', 'url' => 'https://zs2.grajewo.pl/'],
                    ['name' => 'MOPS w Grajewie', 'url' => 'https://mops.grajewo.pl/'],
                ],
                'Miejskie serwisy' => [
                    ['name' => 'Grajewskie Centrum Kultury', 'url' => 'https://www.gckgrajewo.pl/'],
                    ['name' => 'Muzeum Mleka', 'url' => 'https://www.muzeummleka.pl/'],
                    ['name' => 'Miejska Biblioteka', 'url' => 'http://www.biblioteka.grajewo.pl/'],
                ],
            ],
        ],
    ];

    private array $city;
    private string $key;

    public function __construct(string $cityKey)
    {
        $key = strtolower(trim($cityKey));

        if (!isset(self::CITIES[$key])) {
            throw new InvalidArgumentException(sprintf(
                'Unknown APP_CITY "%s". Allowed values: %s.',
                $cityKey,
                implode(', ', array_keys(self::CITIES)),
            ));
        }

        $this->key = $key;
        $this->city = self::CITIES[$key];
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getName(): string
    {
        return $this->city['name'];
    }

    public function getDomain(): string
    {
        return $this->city['domain'];
    }

    /** Logo file prefix, e.g. "a24" for build/images/logo_a24_light.svg */
    public function getLogoKey(): string
    {
        return $this->city['logoKey'];
    }

    public function getShareImage(): string
    {
        return $this->city['shareImage'];
    }

    public function getEmail(): string
    {
        return $this->city['email'];
    }

    public function getFacebook(): string
    {
        return $this->city['facebook'];
    }

    /** @return array<string, array<int, array{name: string, url: string}>> section title => links */
    public function getInstitutions(): array
    {
        return $this->city['institutions'];
    }

    /**
     * The other cities of the network, shown as partner logos in the header.
     *
     * @return array<int, array{key: string, name: string, domain: string, logoKey: string}>
     */
    public function getPartners(): array
    {
        $partners = [];

        foreach (self::CITIES as $key => $city) {
            if ($key === $this->key) {
                continue;
            }

            $partners[] = [
                'key' => $key,
                'name' => $city['name'],
                'domain' => $city['domain'],
                'logoKey' => $city['logoKey'],
            ];
        }

        return $partners;
    }

    /** @return string[] */
    public static function getAvailableKeys(): array
    {
        return array_keys(self::CITIES);
    }
}
