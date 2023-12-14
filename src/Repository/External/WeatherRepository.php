<?php

declare(strict_types=1);

namespace App\Repository\External;

use App\Repository\Cached\CacheKeyPrefix;
use DateInterval;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class WeatherRepository
{
    private const API_URL_CURRENT = 'https://api.openweathermap.org/data/2.5/weather?lat=%s&lon=%s&units=metric&lang=pl&appid=%s';
    private const API_URL_FORECAST = 'https://api.openweathermap.org/data/2.5/forecast?lat=%s&lon=%s&units=metric&lang=pl&appid=%s&cnt=8';

    public function __construct(
        private CacheInterface $cache,
        private HttpClientInterface $httpClient,
        private string $apiKey,
        private string $lat,
        private string $lon,
    ) {
    }

    public function getWeather(): array
    {
        return $this->cache->get(CacheKeyPrefix::WEATHER, function (ItemInterface $item) {
            $item->expiresAfter(new DateInterval('PT1H'));

            try {
                $currentWeatherResponse = $this->httpClient->request(
                    'GET',
                    sprintf(self::API_URL_CURRENT, $this->lat, $this->lon, $this->apiKey),
                );
                $currentWeatherData = $currentWeatherResponse->toArray();

                $currentWeather = [
                    'icon' => $this->getIconName($currentWeatherData['weather'][0]['main']),
                    'description' => $currentWeatherData['weather'][0]['description'],
                    'temperature' => round($currentWeatherData['main']['temp']),
                    'pressure' => $currentWeatherData['main']['pressure'],
                ];

                $forecastWeatherResponse = $this->httpClient->request(
                    'GET',
                    sprintf(self::API_URL_FORECAST, $this->lat, $this->lon, $this->apiKey),
                );

                $forecastWeatherData = $forecastWeatherResponse->toArray();
                $forecastWeatherTempData = [
                    'icon' => [],
                    'description' => [],
                    'temperature' => [],
                    'pressure' => [],
                ];

                foreach ($forecastWeatherData['list'] as $periodWeather) {
                    $forecastWeatherTempData['icon'][] = $periodWeather['weather'][0]['main'];
                    $forecastWeatherTempData['description'][] = $periodWeather['weather'][0]['description'];
                    $forecastWeatherTempData['temperature'][] = $periodWeather['main']['temp'];
                    $forecastWeatherTempData['pressure'][] = $periodWeather['main']['pressure'];
                }

                $forecastWeather = [];
                foreach ($forecastWeatherTempData as $key => $value) {
                    if (is_numeric($value[0])) {
                        $value = array_map('strval', $value);
                    }
                    $valueCounts = array_count_values($value);
                    $mostFrequentValue = array_search(max($valueCounts), $valueCounts);
                    $forecastWeather[$key] = $mostFrequentValue !== false ? $mostFrequentValue : $value[0];
                }

                $forecastWeather['icon'] = $this->getIconName($forecastWeather['icon']);
                $forecastWeather['temperature'] = round((float)$forecastWeather['temperature']);

                return [
                    'today' => $currentWeather,
                    'tomorrow' => $forecastWeather,
                ];
            } catch (Throwable) {
                return null;
            }
        });
    }

    private function getIconName(string $iconType): string
    {
        return match ($iconType) {
            'Thunderstorm' => 'thunderstorm',
            'Drizzle' => 'drizzle',
            'Rain' => 'rain',
            'Snow' => 'snow',
            'Clear' => 'clear',
            'Clouds' => 'clouds',
            default => 'mist',
        };
    }
}
