<?php

namespace App\Enum;

enum UploadDirectory: string
{
    case ARTICLE = 'article';
    case DAILY_IMAGE = 'daily_image';
    case GALERY = 'galery';
    case USER = 'user';
    case USER_REPORT = 'user_report';
}
