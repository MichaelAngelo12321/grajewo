<?php

namespace App\Enum;

enum UploadDirectory: string
{
    case ARTICLE = 'article';
    case DAILY_IMAGE = 'daily_image';
    case GALERY = 'galery';
    case USER_REPORT = 'user_report';
}
