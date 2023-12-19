<?php

declare(strict_types=1);

namespace App\Repository\Cached;

class CacheKeyPrefix
{
    public const AIR_QUALITY = 'air_quality';
    public const ARTICLE_EVENTS_FROM_THIS_MONTH = 'events_from_this_month';
    public const ARTICLE_LATEST_FROM_CATEGORY = 'latest_articles_category_';
    public const ARTICLE_MOST_POPULAR = 'most_popular_articles_limit_';
    public const CATEGORY_ALL = 'categories';
    public const CATEGORY_TOP = 'top_category';
    public const CURRENCY_RATE = 'currency_rate_';
    public const NAME_DAY_TODAY = 'name_day_today';
    public const PHARMACY_DUTY_TODAY = 'pharmacy_duty_today';
    public const SETTING = 'setting_';
    public const USER_REPORT_LAST = 'user_report_last_';
    public const WEATHER = 'weather';
}
