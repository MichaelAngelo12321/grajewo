<?php

declare(strict_types=1);

namespace App\Twig;

use App\Enum\ArticleStatus;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function articleStatusColor(ArticleStatus $status): string
    {
        return match ($status) {
            ArticleStatus::DRAFT => 'secondary',
            ArticleStatus::PUBLISHED => 'success',
            ArticleStatus::HIDDEN => 'warning',
            ArticleStatus::ARCHIVED => 'danger',
        };
    }

    public function articleStatusName(ArticleStatus $status): string
    {
        return match ($status) {
            ArticleStatus::DRAFT => 'Szkic',
            ArticleStatus::PUBLISHED => 'Opublikowany',
            ArticleStatus::HIDDEN => 'Ukryty',
            ArticleStatus::ARCHIVED => 'Zarchiwizowany',
        };
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('article_status_color', [$this, 'articleStatusColor']),
            new TwigFilter('article_status_name', [$this, 'articleStatusName']),
            new TwigFilter('preg_replace', [$this, 'pregReplace']),
        ];
    }

    public function pregReplace($string, $regex = '', $replace = ''): string
    {
        return preg_replace($regex, $replace, $string);
    }
}
