<?php

declare(strict_types=1);

namespace App\Enum;

enum ArticleStatus: int
{
    case DRAFT = 0;
    case PUBLISHED = 1;
    case HIDDEN = 2;
    case ARCHIVED = 3;
}
