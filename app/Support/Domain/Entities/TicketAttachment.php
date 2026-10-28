<?php

namespace App\Support\Domain\Entities;

use App\Shared\Domain\Exceptions\DomainException;
use DateTimeImmutable;

final class TicketAttachment
{
    public const MAX_SIZE_BYTES = 10_485_760;

    public function __construct(
        public ?int $id,
        public int $companyId,
        public int $ticketId,
        public string $originalName,
        public string $disk,
        public string $path,
        public string $mimeType,
        public int $sizeBytes,
        public ?int $uploadedBy,
        public ?DateTimeImmutable $createdAt = null,
        public ?string $uploadedByName = null,
    ) {
        $this->originalName = trim($this->originalName);
        $this->disk = trim($this->disk);
        $this->path = trim($this->path);
        $this->mimeType = trim($this->mimeType);
        $this->uploadedByName = $this->uploadedByName !== null ? trim($this->uploadedByName) : null;

        if ($this->companyId <= 0 || $this->ticketId <= 0) {
            throw DomainException::because('La empresa y el ticket del adjunto son obligatorios.');
        }

        if ($this->originalName === '' || mb_strlen($this->originalName) > 255) {
            throw DomainException::because('El nombre del archivo no es válido.');
        }

        if ($this->disk === '' || $this->path === '' || $this->mimeType === '') {
            throw DomainException::because('El archivo adjunto no tiene una ubicación válida.');
        }

        if ($this->sizeBytes <= 0 || $this->sizeBytes > self::MAX_SIZE_BYTES) {
            throw DomainException::because('El archivo adjunto supera el tamaño permitido.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function auditValues(): array
    {
        return [
            'ticket_id' => $this->ticketId,
            'original_name' => $this->originalName,
            'mime_type' => $this->mimeType,
            'size_bytes' => $this->sizeBytes,
        ];
    }
}
