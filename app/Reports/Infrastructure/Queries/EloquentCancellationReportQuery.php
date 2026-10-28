<?php

namespace App\Reports\Infrastructure\Queries;

use App\BusTicketCancellations\Domain\Enums\BusTicketCancellationResult;
use App\BusTicketCancellations\Infrastructure\Persistence\Eloquent\Models\BusTicketCancellationModel;
use App\Reports\Application\Contracts\CancellationReportQuery;
use App\Reports\Application\DTOs\CancellationReportFilters;
use App\Reports\Application\DTOs\CancellationReportRow;
use App\Shared\Domain\Pagination\Page;
use Generator;
use Illuminate\Database\Eloquent\Builder;

final class EloquentCancellationReportQuery implements CancellationReportQuery
{
    /**
     * @return array{total: int, cancelled: int, not_cancelled: int, pending: int, amount_cents: int}
     */
    public function summary(int $companyId, CancellationReportFilters $filters): array
    {
        $base = $this->baseQuery($companyId, $filters);

        return [
            'total' => (clone $base)->count(),
            'cancelled' => (clone $base)->where('result', BusTicketCancellationResult::Cancelled->value)->count(),
            'not_cancelled' => (clone $base)->where('result', BusTicketCancellationResult::NotCancelled->value)->count(),
            'pending' => (clone $base)->where('result', BusTicketCancellationResult::Pending->value)->count(),
            'amount_cents' => (int) (clone $base)->sum('amount_cents'),
        ];
    }

    /**
     * @return Page<CancellationReportRow>
     */
    public function paginate(int $companyId, CancellationReportFilters $filters, int $page = 1, int $perPage = 20): Page
    {
        $paginator = $this->baseQuery($companyId, $filters)
            ->with(['operator', 'supportTicket'])
            ->orderByDesc('requested_at')
            ->orderByDesc('id')
            ->paginate(max(1, $perPage), page: max(1, $page));

        return new Page(
            items: $paginator->getCollection()
                ->map(fn (BusTicketCancellationModel $cancellation) => $this->toRow($cancellation))
                ->all(),
            total: $paginator->total(),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
        );
    }

    /**
     * @return Generator<int, CancellationReportRow>
     */
    public function exportRows(int $companyId, CancellationReportFilters $filters): Generator
    {
        $query = $this->baseQuery($companyId, $filters)
            ->with(['operator', 'supportTicket'])
            ->orderByDesc('requested_at')
            ->orderByDesc('id');

        foreach ($query->lazy(500) as $cancellation) {
            yield $this->toRow($cancellation);
        }
    }

    /**
     * @return Builder<BusTicketCancellationModel>
     */
    private function baseQuery(int $companyId, CancellationReportFilters $filters): Builder
    {
        return BusTicketCancellationModel::query()
            ->where('company_id', $companyId)
            ->whereBetween('requested_at', [$filters->period->from, $filters->period->to])
            ->when(filled($filters->term), function (Builder $query) use ($filters): void {
                $term = (string) $filters->term;

                $query->where(function (Builder $query) use ($term): void {
                    $query->where('ticket_number', 'like', "%{$term}%")
                        ->orWhere('reason', 'like', "%{$term}%")
                        ->orWhere('authorization_reference', 'like', "%{$term}%")
                        ->orWhereHas('supportTicket', fn (Builder $ticket) => $ticket->where('folio', 'like', "%{$term}%"));
                });
            })
            ->when($filters->result !== null, fn (Builder $query) => $query->where('result', $filters->result->value))
            ->when($filters->operatorId !== null, fn (Builder $query) => $query->where('operator_id', $filters->operatorId));
    }

    private function toRow(BusTicketCancellationModel $cancellation): CancellationReportRow
    {
        return new CancellationReportRow(
            id: $cancellation->id,
            ticketNumber: $cancellation->ticket_number,
            supportTicketFolio: $cancellation->supportTicket?->folio,
            requestedAt: $cancellation->requested_at->toDateTimeImmutable(),
            executedAt: $cancellation->executed_at?->toDateTimeImmutable(),
            amountCents: $cancellation->amount_cents,
            operatorName: $cancellation->operator->name,
            result: BusTicketCancellationResult::from($cancellation->result),
            reason: $cancellation->reason,
            authorizationReference: $cancellation->authorization_reference,
        );
    }
}
