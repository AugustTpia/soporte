<?php

namespace App\Reports\Infrastructure\Export;

use App\Reports\Application\Contracts\PdfReportExporter as PdfReportExporterContract;
use App\Reports\Application\DTOs\ReportDocument;
use App\Reports\Application\DTOs\ReportFile;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;

final class PdfReportExporter implements PdfReportExporterContract
{
    private const MAX_ROWS = 5000;

    public function export(ReportDocument $document): ReportFile
    {
        $rows = [];
        $truncated = false;

        foreach ($document->rows as $row) {
            if (count($rows) >= self::MAX_ROWS) {
                $truncated = true;
                break;
            }

            $rows[] = $row;
        }

        $html = View::make('reports.pdf.document', [
            'document' => $document,
            'rows' => $rows,
            'truncated' => $truncated,
            'maxRows' => self::MAX_ROWS,
        ])->render();

        $output = Pdf::loadHTML($html)
            ->setPaper('a4', 'landscape')
            ->output();

        $filename = sprintf(
            'reporte-%s-%s-%s.pdf',
            $document->type->value,
            $document->period->from->format('Ymd'),
            $document->period->to->format('Ymd'),
        );

        return new ReportFile(
            filename: $filename,
            mimeType: 'application/pdf',
            writer: function () use ($output): void {
                echo $output;
            },
        );
    }
}
