<?php

namespace App\Attachments\Application\DTOs;

final readonly class EvidenceFileInput
{
    public function __construct(
        public string $temporaryPath,
        public string $originalName,
        public string $extension,
        public string $mimeType,
        public int $sizeBytes,
    ) {}
}
