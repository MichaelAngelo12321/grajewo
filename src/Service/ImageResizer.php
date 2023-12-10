<?php

declare(strict_types=1);

namespace App\Service;

use Imagine\Gd\Imagine;

class ImageResizer
{
    public function __construct(
        private Imagine $imagine,
        private int $maxWidth,
        private int $maxHeight,
        private string $publicDirectory,
    ) {
    }

    public function resize(string $path, int $width = null, int $height = null): void
    {
        $maxWidth = $width ?? $this->maxWidth;
        $maxHeight = $height ?? $this->maxHeight;

        $image = $this->imagine->open($this->publicDirectory . $path);

        // resize only if image is bigger than max width or height
        if ($image->getSize()->getWidth() > $maxWidth && $image->getSize()->getHeight() > $maxHeight) {
            $image->resize($image->getSize()->widen($width ?? $this->maxWidth));
            $image->save($this->publicDirectory . $path);
        }
    }
}
