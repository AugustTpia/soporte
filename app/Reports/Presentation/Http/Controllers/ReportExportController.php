<?php

namespace App\Reports\Presentation\Http\Controllers;

use App\BusTicketCancellations\Domain\Enums\BusTicketCancellationResult;
use App\Companies\Domain\Repositories\CompanyRepository;
use App\Maintenance\Domain\Enums\MaintenanceExecutionStatus;
use App\Maintenance\Domain\Enums\MaintenanceExecutionType;
use App\Reports\Application\Contracts\CsvReportExporter;
use App\Reports\Application\Contracts\PdfReportExporter;
use App\Reports\Application\DTOs\CancellationReportFilters;
use App\Reports\Application\DTOs\MaintenanceReportFilters;
use App\Reports\Application\DTOs\ReportPeriod;
use App\Reports\Application\DTOs\TicketReportFilters;
use App\Reports\Application\UseCases\BuildCancellationReport;
use App\Reports\Application\UseCases\BuildMaintenanceReport;
use App\Reports\Application\UseCases\BuildTicketReport;
use App\Reports\Domain\Enums\ReportFormat;
use App\Reports\Domain\Enums\ReportType;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Application\Contracts\TenantContext;
use App\Shared\Domain\Exceptions\DomainException;
use App\Support\Domain\Enums\TicketCategory;
use App\Support\Domain\Enums\TicketPriority;
use App\Support\Domain\Enums\TicketStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ReportExportController
{
    public function __invoke(Request $request, string $report): StreamedResponse
    {
        $type = ReportType::tryFrom($report);

        if ($type === null) {
            abort(404);
        }

        Gate::authorize('viewAny', $type->authorizationModel());

        $companyId = app(TenantContext::class)->companyId();

        if ($companyId === null) {
            abort(403);
        }

        $company = app(CompanyRepository::class)->find($companyId);

        if ($company === null) {
            abort(403);
        }

        if ($type->requiredFeature() !== null && ! $company->hasFeature($type->requiredFeature())) {
            abort(403);
        }

        $validated = $request->validate($this->rules($type) + [
            'format' => ['required', Rule::enum(ReportFormat::class)],
            'search' => ['nullable', 'string', 'max:200'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $format = ReportFormat::from((string) $validated['format']);
        $period = $this->period($validated);

        $user = $request->user();

        $document = match ($type) {
            ReportType::Tickets => app(BuildTicketReport::class)->document(
                $companyId,
                $this->ticketFilters($period, $validated),
                $company->name,
                (string) $user?->name,
            ),
            ReportType::Maintenance => app(BuildMaintenanceReport::class)->document(
                $companyId,
                $this->maintenanceFilters($period, $validated),
                $company->name,
                (string) $user?->name,
            ),
            ReportType::Cancellations => app(BuildCancellationReport::class)->document(
                $companyId,
                $this->cancellationFilters($period, $validated),
                $company->name,
                (string) $user?->name,
            ),
        };

        $file = match ($format) {
            ReportFormat::Csv => app(CsvReportExporter::class)->export($document),
            ReportFormat::Pdf => app(PdfReportExporter::class)->export($document),
        };

        app(AuditLogger::class)->log(
            event: 'report.exported',
            description: "Se exportó el {$type->label()} en formato {$format->label()}.",
            subjectType: 'report',
            subjectId: null,
            metadata: [
                'report' => $type->value,
                'format' => $format->value,
                'filters' => $document->filterLabels,
                'rows' => $document->totalRows,
            ],
            companyId: $companyId,
        );

        return response()->streamDownload(function () use ($file): void {
            ($file->writer)();
        }, $file->filename, [
            'Content-Type' => $file->mimeType,
            'Cache-Control' => 'private, no-store, no-cache, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(ReportType $type): array
    {
        return match ($type) {
            ReportType::Tickets => [
                'status' => ['nullable', Rule::enum(TicketStatus::class)],
                'priority' => ['nullable', Rule::enum(TicketPriority::class)],
                'category' => ['nullable', Rule::enum(TicketCategory::class)],
                'technician' => ['nullable', 'integer', 'min:1'],
            ],
            ReportType::Maintenance => [
                'status' => ['nullable', Rule::enum(MaintenanceExecutionStatus::class)],
                'technician' => ['nullable', 'integer', 'min:1'],
                'type' => ['nullable', Rule::enum(MaintenanceExecutionType::class)],
                'plan' => ['nullable', 'integer', 'min:1'],
            ],
            ReportType::Cancellations => [
                'result' => ['nullable', Rule::enum(BusTicketCancellationResult::class)],
                'operator' => ['nullable', 'integer', 'min:1'],
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function period(array $validated): ReportPeriod
    {
        try {
            return ReportPeriod::fromStrings(
                is_string($validated['from'] ?? null) ? $validated['from'] : null,
                is_string($validated['to'] ?? null) ? $validated['to'] : null,
            );
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['to' => $exception->getMessage()]);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function ticketFilters(ReportPeriod $period, array $validated): TicketReportFilters
    {
        return new TicketReportFilters(
            period: $period,
            term: $this->nullableString($validated['search'] ?? null),
            status: isset($validated['status']) ? TicketStatus::from((string) $validated['status']) : null,
            priority: isset($validated['priority']) ? TicketPriority::from((string) $validated['priority']) : null,
            category: isset($validated['category']) ? TicketCategory::from((string) $validated['category']) : null,
            technicianId: $this->nullableInt($validated['technician'] ?? null),
        );
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function maintenanceFilters(ReportPeriod $period, array $validated): MaintenanceReportFilters
    {
        return new MaintenanceReportFilters(
            period: $period,
            term: $this->nullableString($validated['search'] ?? null),
            status: isset($validated['status']) ? MaintenanceExecutionStatus::from((string) $validated['status']) : null,
            type: isset($validated['type']) ? MaintenanceExecutionType::from((string) $validated['type']) : null,
            technicianId: $this->nullableInt($validated['technician'] ?? null),
            planId: $this->nullableInt($validated['plan'] ?? null),
        );
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function cancellationFilters(ReportPeriod $period, array $validated): CancellationReportFilters
    {
        return new CancellationReportFilters(
            period: $period,
            term: $this->nullableString($validated['search'] ?? null),
            result: isset($validated['result']) ? BusTicketCancellationResult::from((string) $validated['result']) : null,
            operatorId: $this->nullableInt($validated['operator'] ?? null),
        );
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }
}
