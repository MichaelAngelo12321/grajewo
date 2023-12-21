<?php

namespace App\Repository\Cached;

use App\Repository\GasStationPriceRepository;
use App\Repository\GasStationRepository;
use DateTime;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class GasStationCachedRepository
{
    public function __construct(
        private GasStationRepository $gasStationRepository,
        private GasStationPriceRepository $gasStationPriceRepository,
        private CacheInterface $cache,
    ) {
    }

    public function findStationsWithPrices(): array
    {
        return $this->cache->get(CacheKeyPrefix::GAS_STATION_PRICES, function (ItemInterface $item) {
            $item->expiresAt(new DateTime('today 23:59:59'));

            $stations = $this->gasStationRepository->findAll();
            $stationsWithPrices = [];

            foreach ($stations as $station) {
                $foundPrices = $this->gasStationPriceRepository->findLatestStationPrice($station);
                $stationPrices = [];

                foreach ($foundPrices as $foundPrice) {
                    if (
                        !isset($stationPrices[$foundPrice->getType()])
                        || $stationPrices[$foundPrice->getType()]->getPrice() === null
                    ) {
                        $stationPrices[$foundPrice->getType()] = $foundPrice;
                    }
                }

                $stationsWithPrices[] = [
                    'station' => $station,
                    'prices' => $stationPrices,
                ];
            }

            return $stationsWithPrices;
        });
    }
}
