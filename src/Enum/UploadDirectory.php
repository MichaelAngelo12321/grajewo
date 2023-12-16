<?php

namespace App\Enum;

enum UploadDirectory: string
{
    case ARTICLE = 'article';
    case GALERY = 'galery';
    case USER_REPORT = 'user_report';
}
