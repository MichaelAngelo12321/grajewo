<?php

declare(strict_types=1);

namespace App\Service;

class FileCleaner
{
    public function __construct(
        private string $publicDirectory,
    ) {
    }

    public function removeFile(string $path): void
    {
        $filePath = sprintf('%s/%s', $this->publicDirectory, $path);

        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}
