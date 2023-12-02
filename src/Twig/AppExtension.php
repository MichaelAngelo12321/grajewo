<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('preg_replace', [$this, 'pregReplace']),
        ];
    }

    public function pregReplace($string, $regex = '', $replace = ''): string
    {
        return preg_replace($regex, $replace, $string);
    }
}
