<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\UploadDirectory;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader
{
    public function __construct(
        private string $publicDirectory,
        private SluggerInterface $slugger,
        private string $targetDirectory,
    ) {
    }

    public function upload(UploadedFile $file, UploadDirectory $uploadDirectory, array $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']): string
    {
        $mimeType = $file->getMimeType();

        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            throw new FileException(sprintf('Niedozwolony typ pliku: %s. Dozwolone typy: %s', $mimeType, implode(', ', $allowedMimeTypes)));
        }

        $fileName = $this->getTargetFilename($file);
        $file->move($this->getTargetDirectory($uploadDirectory), $fileName);

        return sprintf('%s/%s', $this->getTargetDirectory($uploadDirectory, false), $fileName);
    }

    private function getTargetFilename(UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);

        return $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
    }

    private function getTargetDirectory(UploadDirectory $uploadDirectory, bool $absolute = true): string
    {
        return $absolute
            ? sprintf(
                '%s/%s/%s/%s',
                $this->publicDirectory,
                $this->targetDirectory,
                $uploadDirectory->value,
                date('Y/m'),
            )
            : sprintf(
                '/%s/%s/%s',
                $this->targetDirectory,
                $uploadDirectory->value,
                date('Y/m'),
            );
    }
}
