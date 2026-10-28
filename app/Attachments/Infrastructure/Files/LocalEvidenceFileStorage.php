<?php

namespace App\Attachments\Infrastructure\Files;

use App\Attachments\Application\Contracts\EvidenceFileStorage;
use App\Attachments\Application\DTOs\EvidenceFileInput;
use App\Attachments\Application\DTOs\StoredEvidenceFile;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

final class LocalEvidenceFileStorage implements EvidenceFileStorage
{
    private const DISK = 'local';

    public function store(string $directory, EvidenceFileInput $file): StoredEvidenceFile
    {
        $extension = preg_replace('/[^a-z0-9]/', '', mb_strtolower($file->extension)) ?: 'bin';
        $filename = Str::uuid()->toString().'.'.$extension;

        $path = Storage::disk(self::DISK)->putFileAs(
            $directory,
            new File($file->temporaryPath),
            $filename,
        );

        if (! is_string($path) || $path === '') {
            throw new RuntimeException('No se pudo guardar la evidencia adjunta.');
        }

        return new StoredEvidenceFile(self::DISK, $path);
    }

    public function delete(string $disk, string $path): void
    {
        Storage::disk($disk)->delete($path);
    }
}
