<?php

namespace App\Reports\Application\DTOs;

final readonly class ReportMetric
{
    public function __construct(
        public string $label,
        public string $value,
        public ?string $hint = null,
        public string $tone = 'royal',
    ) {}
}
