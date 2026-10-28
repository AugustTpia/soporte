<?php

namespace App\Reports\Domain\Enums;

enum ReportFormat: string
{
    case Csv = 'csv';
    case Pdf = 'pdf';

    public function label(): string
    {
        return match ($this) {
            self::Csv => 'CSV',
            self::Pdf => 'PDF',
        };
    }

    public function extension(): string
    {
        return $this->value;
    }

    public function mimeType(): string
    {
        return match ($this) {
            self::Csv => 'text/csv; charset=UTF-8',
            self::Pdf => 'application/pdf',
        };
    }
}
