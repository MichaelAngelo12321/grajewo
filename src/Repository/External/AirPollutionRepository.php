<?php

declare(strict_types=1);

namespace App\Repository\External;

use App\Repository\Cached\CacheKeyPrefix;
use DateInterval;
use DOMDocument;
use DOMXPath;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class AirPollutionRepository
{
    public function __construct(
        private CacheInterface $cache,
        private HttpClientInterface $httpClient,
        private string $dataUrl
    ) {
    }

    public function getAirQuality(): ?array
    {
        return $this->cache->get(CacheKeyPrefix::AIR_QUALITY, function (ItemInterface $item) {
            $item->expiresAfter(new DateInterval('PT1H'));

            try {
                $response = $this->httpClient->request(
                    'GET',
                    $this->dataUrl,
                );

                $responseHtml = $response->getContent();

                $dom = new DOMDocument();
                @$dom->loadHTML($responseHtml);
                $xpath = new DOMXPath($dom);

                $metricRows = $xpath->query('//table[@class="table table-bordered"]/tbody/tr');
                $data = [];

                foreach ($metricRows as $row) {
                    $cells = $xpath->query('td | th', $row);
                    $rowData = [];

                    foreach ($cells as $cell) {
                        $rowData[] = trim($cell->nodeValue);
                    }

                    if ($rowData[1] !== '' && $rowData[2] !== '' && $rowData[4] !== '') {
                        $data[] = $rowData;
                    }
                }

                array_pop($data);
                array_pop($data);
                array_pop($data);

                $lastMetric = end($data);

                $pm10 = (float)str_replace(',', '.', $lastMetric[1]);
                $pm2_5 = (float)str_replace(',', '.', $lastMetric[2]);
                $no2 = (float)str_replace(',', '.', $lastMetric[4]);

                return $this->getAirQualityIndex($pm10, $pm2_5, $no2);
            } catch (Throwable) {
                return null;
            }
        });
    }

    public function getAirQualityIndex(float $pm10, float $pm2_5, float $no2): array
    {
        if ($pm10 <= 20 && $pm2_5 <= 10 && $no2 <= 40) {
            return ['Bardzo dobra', 5];
        } elseif ($pm10 <= 35 && $pm2_5 <= 25 && $no2 <= 100) {
            return ['Dobra', 4];
        } elseif ($pm10 <= 50 && $pm2_5 <= 35 && $no2 <= 200) {
            return ['Umiarkowana', 3];
        } elseif ($pm10 <= 100 && $pm2_5 <= 50 && $no2 <= 400) {
            return ['Zła', 2];
        } else {
            return ['Bardzo zła', 1];
        }
    }
}
