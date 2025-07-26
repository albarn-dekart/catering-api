<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploader
{
    private string $targetDirectory;

    public function __construct(string $targetDirectory)
    {
        $this->targetDirectory = $targetDirectory;
    }

    private array $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp'
    ];

    private int $maxFileSize = 2 * 1024 * 1024; // 2MB

    public function upload(UploadedFile $file): string
    {
        // Validate file type
        if (!in_array($file->getMimeType(), $this->allowedMimeTypes)) {
            throw new FileException('Invalid file type. Allowed types: ' . implode(', ', $this->allowedMimeTypes));
        }

        // Validate file size
        if ($file->getSize() > $this->maxFileSize) {
            throw new FileException('File is too large. Maximum size: ' . ($this->maxFileSize / 1024 / 1024) . 'MB');
        }
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = transliterator_transliterate(
            'Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()',
            $originalFilename
        );
        $fileName = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        try {
            $file->move($this->targetDirectory, $fileName);
        } catch (FileException $e) {
            throw new FileException('Could not upload file: ' . $e->getMessage());
        }

        return $fileName;
    }

    public function remove(string $fileName): void
    {
        $filePath = $this->targetDirectory . '/' . $fileName;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}