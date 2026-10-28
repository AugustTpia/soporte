<?php

namespace App\Reports\Application\Contracts;

use App\Reports\Application\DTOs\ReportDocument;
use App\Reports\Application\DTOs\ReportFile;

interface CsvReportExporter
{
    public function export(ReportDocument $document): ReportFile;
}
