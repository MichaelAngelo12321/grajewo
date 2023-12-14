<?php

declare(strict_types=1);

namespace App\Repository\External;

use App\Repository\Cached\CacheKeyPrefix;
use DateTime;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class CurrencyRateRepository
{
    private const API_URL = 'https://api.nbp.pl/api/exchangerates/rates/a/%s/?format=json';

    public function __construct(
        private CacheInterface $cache,
        private HttpClientInterface $httpClient,
    ) {
    }

    public function getRate(array $currencyCodes): ?array
    {
        return $this->cache->get(CacheKeyPrefix::CURRENCY_RATE . implode($currencyCodes), function (ItemInterface $item) use ($currencyCodes) {
            $item->expiresAt(new DateTime('today 23:59:59'));

            try {
                $rates = [];

                foreach ($currencyCodes as $currencyCode) {
                    $rate = $this->httpClient->request('GET', sprintf(self::API_URL, $currencyCode));
                    $rates[$currencyCode] = round($rate->toArray()['rates'][0]['mid'], 2);
                }

                return $rates;
            } catch (Throwable) {
                return null;
            }
        });
    }
}
