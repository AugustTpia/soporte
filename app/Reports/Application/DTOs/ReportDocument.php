<?php

namespace App\Reports\Application\DTOs;

use App\Reports\Domain\Enums\ReportType;
use DateTimeImmutable;

final readonly class ReportDocument
{
    /**
     * @param  array<int, string>  $headings
     * @param  array<int, ReportMetric>  $metrics
     * @param  array<string, string>  $filterLabels
     * @param  iterable<int, array<int, string>>  $rows
     */
    public function __construct(
        public ReportType $type,
        public string $title,
        public string $companyName,
        public ReportPeriod $period,
        public array $filterLabels,
        public array $metrics,
        public array $headings,
        public iterable $rows,
        public int $totalRows,
        public string $generatedBy,
        public DateTimeImmutable $generatedAt,
    ) {}
}
