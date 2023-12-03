<?php

declare(strict_types=1);

namespace App\Repository\Cached;

class CacheKeyPrefix
{
    public const ARTICLE_LATEST_FROM_CATEGORY = 'latest_articles_category_';
    public const ARTICLE_MOST_POPULAR = 'most_popular_articles_limit_';
    public const CATEGORY_ALL = 'categories';
    public const CATEGORY_TOP = 'top_category';
}
