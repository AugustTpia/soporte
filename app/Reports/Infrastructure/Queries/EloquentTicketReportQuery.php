<?php

namespace App\Reports\Infrastructure\Queries;

use App\Reports\Application\Contracts\TicketReportQuery;
use App\Reports\Application\DTOs\TicketReportFilters;
use App\Reports\Application\DTOs\TicketReportRow;
use App\Shared\Domain\Pagination\Page;
use App\Support\Domain\Enums\TicketCategory;
use App\Support\Domain\Enums\TicketPriority;
use App\Support\Domain\Enums\TicketStatus;
use App\Support\Infrastructure\Persistence\Eloquent\Models\SupportTicketModel;
use Generator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class EloquentTicketReportQuery implements TicketReportQuery
{
    /**
     * @return array{total: int, unresolved: int, resolved: int, cancelled: int, urgent: int, avg_resolution_hours: float|null}
     */
    public function summary(int $companyId, TicketReportFilters $filters): array
    {
        $base = $this->baseQuery($companyId, $filters);
        $unresolvedStatuses = $this->unresolvedStatuses();

        $resolved = (clone $base)
            ->whereNotNull('resolved_at')
            ->get(['opened_at', 'resolved_at']);

        return [
            'total' => (clone $base)->count(),
            'unresolved' => (clone $base)->whereIn('status', $unresolvedStatuses)->count(),
            'resolved' => $resolved->count(),
            'cancelled' => (clone $base)->where('status', TicketStatus::Cancelled->value)->count(),
            'urgent' => (clone $base)
                ->where('priority', 'urgent')
                ->whereIn('status', $unresolvedStatuses)
                ->count(),
            'avg_resolution_hours' => $this->averageResolutionHours($resolved),
        ];
    }

    /**
     * @return Page<TicketReportRow>
     */
    public function paginate(int $companyId, TicketReportFilters $filters, int $page = 1, int $perPage = 20): Page
    {
        $paginator = $this->baseQuery($companyId, $filters)
            ->with(['location', 'activeAssignments.user'])
            ->orderByDesc('opened_at')
            ->orderByDesc('id')
            ->paginate(max(1, $perPage), page: max(1, $page));

        return new Page(
            items: $paginator->getCollection()
                ->map(fn (SupportTicketModel $ticket) => $this->toRow($ticket))
                ->all(),
            total: $paginator->total(),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
        );
    }

    /**
     * @return Generator<int, TicketReportRow>
     */
    public function exportRows(int $companyId, TicketReportFilters $filters): Generator
    {
        $query = $this->baseQuery($companyId, $filters)
            ->with(['location', 'activeAssignments.user'])
            ->orderByDesc('opened_at')
            ->orderByDesc('id');

        foreach ($query->lazy(500) as $ticket) {
            yield $this->toRow($ticket);
        }
    }

    /**
     * @return Builder<SupportTicketModel>
     */
    private function baseQuery(int $companyId, TicketReportFilters $filters): Builder
    {
        return SupportTicketModel::query()
            ->where('company_id', $companyId)
            ->whereBetween('opened_at', [$filters->period->from, $filters->period->to])
            ->when(filled($filters->term), function (Builder $query) use ($filters): void {
                $term = (string) $filters->term;

                $query->where(function (Builder $query) use ($term): void {
                    $query->where('folio', 'like', "%{$term}%")
                        ->orWhere('subject', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%")
                        ->orWhere('requester_name', 'like', "%{$term}%")
                        ->orWhere('requester_contact', 'like', "%{$term}%");
                });
            })
            ->when($filters->status !== null, fn (Builder $query) => $query->where('status', $filters->status->value))
            ->when($filters->priority !== null, fn (Builder $query) => $query->where('priority', $filters->priority->value))
            ->when($filters->category !== null, fn (Builder $query) => $query->where('category', $filters->category->value))
            ->when($filters->technicianId !== null, function (Builder $query) use ($filters): void {
                $query->whereHas(
                    'activeAssignments',
                    fn (Builder $assignment) => $assignment->where('user_id', $filters->technicianId),
                );
            });
    }

    private function toRow(SupportTicketModel $ticket): TicketReportRow
    {
        $resolutionHours = null;

        if ($ticket->resolved_at !== null) {
            $resolutionHours = round($ticket->opened_at->diffInSeconds($ticket->resolved_at) / 3600, 2);
        }

        return new TicketReportRow(
            id: $ticket->id,
            folio: $ticket->folio,
            openedAt: $ticket->opened_at->toDateTimeImmutable(),
            subject: $ticket->subject,
            category: TicketCategory::from($ticket->category),
            priority: TicketPriority::from($ticket->priority),
            status: TicketStatus::from($ticket->status),
            locationName: $ticket->location?->name,
            technicians: $ticket->activeAssignments
                ->map(fn ($assignment) => $assignment->user->name)
                ->filter()
                ->values()
                ->all(),
            resolvedAt: $ticket->resolved_at?->toDateTimeImmutable(),
            closedAt: $ticket->closed_at?->toDateTimeImmutable(),
            resolutionHours: $resolutionHours,
        );
    }

    /**
     * @param  Collection<int, SupportTicketModel>  $tickets
     */
    private function averageResolutionHours(Collection $tickets): ?float
    {
        $seconds = 0;
        $count = 0;

        foreach ($tickets as $ticket) {
            if ($ticket->resolved_at === null) {
                continue;
            }

            $seconds += $ticket->opened_at->diffInSeconds($ticket->resolved_at);
            $count++;
        }

        return $count === 0 ? null : round(($seconds / $count) / 3600, 2);
    }

    /**
     * @return array<int, string>
     */
    private function unresolvedStatuses(): array
    {
        return [
            TicketStatus::Open->value,
            TicketStatus::Assigned->value,
            TicketStatus::InProgress->value,
            TicketStatus::Waiting->value,
        ];
    }
}
