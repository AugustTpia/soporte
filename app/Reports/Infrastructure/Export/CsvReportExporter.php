<?php

namespace App\Reports\Infrastructure\Export;

use App\Reports\Application\Contracts\CsvReportExporter as CsvReportExporterContract;
use App\Reports\Application\DTOs\ReportDocument;
use App\Reports\Application\DTOs\ReportFile;

final class CsvReportExporter implements CsvReportExporterContract
{
    public function export(ReportDocument $document): ReportFile
    {
        $filename = sprintf(
            'reporte-%s-%s-%s.csv',
            $document->type->value,
            $document->period->from->format('Ymd'),
            $document->period->to->format('Ymd'),
        );

        return new ReportFile(
            filename: $filename,
            mimeType: 'text/csv; charset=UTF-8',
            writer: function () use ($document): void {
                $output = fopen('php://output', 'w');

                if ($output === false) {
                    return;
                }

                fwrite($output, "\xEF\xBB\xBF");
                fputcsv($output, $document->headings);

                foreach ($document->rows as $row) {
                    fputcsv($output, array_map($this->safeCell(...), $row));
                }

                fclose($output);
            },
        );
    }

    private function safeCell(string $value): string
    {
        if (preg_match('/^[=+\-@\t\r]/', $value) === 1) {
            return "'".$value;
        }

        return $value;
    }
}
