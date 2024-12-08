<?php

declare(strict_types=1);

namespace App\Enum;

enum UploadDirectory: string
{
    case ARTICLE = 'article';
    case DAILY_IMAGE = 'daily_image';
    case GALLERY = 'gallery';
    case PROMO_ITEM = 'promo_item';
    case USER = 'user';
    case USER_REPORT = 'user_report';
}
