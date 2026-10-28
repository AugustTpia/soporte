<?php

namespace App\BusTicketCancellations\Presentation\Http\Controllers;

use App\BusTicketCancellations\Application\DTOs\BusTicketCancellationFilters;
use App\BusTicketCancellations\Application\UseCases\ExportBusTicketCancellations;
use App\BusTicketCancellations\Domain\Enums\BusTicketCancellationResult;
use App\BusTicketCancellations\Infrastructure\Persistence\Eloquent\Models\BusTicketCancellationModel;
use App\Shared\Application\Contracts\TenantContext;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class BusTicketCancellationExportController
{
    public function __invoke(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', BusTicketCancellationModel::class);

        $companyId = app(TenantContext::class)->companyId();

        if ($companyId === null) {
            abort(403);
        }

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'result' => ['nullable', Rule::enum(BusTicketCancellationResult::class)],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $filters = new BusTicketCancellationFilters(
            term: $this->nullableString($validated['search'] ?? null),
            result: isset($validated['result'])
                ? BusTicketCancellationResult::from((string) $validated['result'])
                : null,
            from: isset($validated['from']) ? new DateTimeImmutable((string) $validated['from']) : null,
            to: isset($validated['to']) ? new DateTimeImmutable((string) $validated['to']) : null,
        );

        $export = app(ExportBusTicketCancellations::class);
        $filename = 'cancelaciones-boletos-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($export, $companyId, $filters): void {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $export->headings(), ',', '"', '');

            foreach ($export->execute($companyId, $filters) as $row) {
                fputcsv($output, array_map($this->csvValue(...), $row), ',', '"', '');
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store, no-cache, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function csvValue(mixed $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'".$value : $value;
    }
}
