<?php

declare(strict_types=1);

namespace App\Repository\Cached;

use App\Entity\DailyImage;
use App\Entity\DailyVideo;
use App\Repository\DailyImageRepository;
use App\Repository\DailyVideoRepository;
use DateTime;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class UserContentCachedRepository
{
    public function __construct(
        private CacheInterface $cache,
        private DailyImageRepository $dailyImageRepository,
        private DailyVideoRepository $dailyVideoRepository,
    ) {
    }

    public function findLatestDailyImage(): ?DailyImage
    {
        return $this->cache->get(CacheKeyPrefix::LATEST_DAILY_IMAGE, function (ItemInterface $item) {
            $item->expiresAt(new DateTime('tomorrow 00:00:00'));

            return $this->dailyImageRepository->findOneBy(['isPublished' => true], ['createdAt' => 'DESC']);
        });
    }

    public function findLatestDailyVideo(): ?DailyVideo
    {
        return $this->cache->get(CacheKeyPrefix::LATEST_DAILY_VIDEO, function (ItemInterface $item) {
            $item->expiresAt(new DateTime('tomorrow 00:00:00'));

            $video = $this->dailyVideoRepository->findOneBy(['isPublished' => true], ['createdAt' => 'DESC']);

            if ($video === null) {
                return null;
            }

            $videoUrl = $video->getVideoUrl();

            // Pattern for standard watch URLs, embed URLs, and short URLs
            // This handles URLs with additional parameters correctly by extracting just the video ID
            if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $videoUrl, $matches)) {
                $video->setVideoUrl('https://www.youtube.com/embed/' . $matches[1]);
            } elseif (!str_contains($videoUrl, 'embed')) {
                // Fallback for simple cases if regex fails but it's a watch URL
                $videoUrl = str_replace('watch?v=', 'embed/', $videoUrl);
                $video->setVideoUrl($videoUrl);
            }

            return $video;
        });
    }
}
