<?php

declare(strict_types=1);

namespace App\EventListener\Entity;

use App\Entity\ArticleComment;
use App\Repository\Cached\CacheKeyPrefix;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Contracts\Cache\CacheInterface;

#[AsEntityListener(event: Events::postPersist, method: 'clearCache', entity: ArticleComment::class)]
#[AsEntityListener(event: Events::postRemove, method: 'clearCache', entity: ArticleComment::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'clearCache', entity: ArticleComment::class)]
class ArticleCommentListener
{
    public function __construct(
        private CacheInterface $cache,
    ) {
    }

    public function clearCache(): void
    {
        // Pętla od 1 do 10, żeby wyczyścić różne możliwe limitowane cache dla najnowszych komentarzy.
        for ($i = 1; $i <= 10; $i++) {
            $this->cache->delete(CacheKeyPrefix::ARTICLE_COMMENT_LAST . $i);
        }
    }
}
