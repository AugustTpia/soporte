<?php

namespace App\Attachments\Application\DTOs;

final readonly class StoredEvidenceFile
{
    public function __construct(
        public string $disk,
        public string $path,
    ) {}
}
