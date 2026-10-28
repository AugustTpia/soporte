<?php

namespace App\Attachments\Application\Contracts;

use App\Attachments\Application\DTOs\EvidenceFileInput;
use App\Attachments\Application\DTOs\StoredEvidenceFile;

interface EvidenceFileStorage
{
    public function store(string $directory, EvidenceFileInput $file): StoredEvidenceFile;

    public function delete(string $disk, string $path): void;
}
