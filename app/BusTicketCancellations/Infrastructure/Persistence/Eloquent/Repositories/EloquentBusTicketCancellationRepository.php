<?php

namespace App\BusTicketCancellations\Infrastructure\Persistence\Eloquent\Repositories;

use App\BusTicketCancellations\Application\DTOs\BusTicketCancellationFilters;
use App\BusTicketCancellations\Domain\Entities\BusTicketCancellation;
use App\BusTicketCancellations\Domain\Enums\BusTicketCancellationResult;
use App\BusTicketCancellations\Domain\Repositories\BusTicketCancellationRepository;
use App\BusTicketCancellations\Infrastructure\Persistence\Eloquent\Mappers\BusTicketCancellationMapper;
use App\BusTicketCancellations\Infrastructure\Persistence\Eloquent\Models\BusTicketCancellationModel;
use App\Shared\Domain\Exceptions\DomainException;
use App\Shared\Domain\Pagination\Page;
use Illuminate\Database\Eloquent\Builder;

final class EloquentBusTicketCancellationRepository implements BusTicketCancellationRepository
{
    public function __construct(private readonly BusTicketCancellationMapper $mapper) {}

    /**
     * @return Page<BusTicketCancellation>
     */
    public function search(
        int $companyId,
        BusTicketCancellationFilters $filters,
        int $page = 1,
        int $perPage = 20,
    ): Page {
        $paginator = $this->filteredQuery($companyId, $filters)
            ->with(['operator', 'supportTicket', 'creator'])
            ->orderByDesc('requested_at')
            ->orderByDesc('id')
            ->paginate($perPage, page: max(1, $page));

        return new Page(
            items: $paginator->getCollection()
                ->map(fn (BusTicketCancellationModel $model) => $this->mapper->toDomain($model))
                ->all(),
            total: $paginator->total(),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
        );
    }

    public function find(int $id, int $companyId): ?BusTicketCancellation
    {
        $model = BusTicketCancellationModel::query()
            ->with(['operator', 'supportTicket', 'creator'])
            ->where('company_id', $companyId)
            ->find($id);

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function save(BusTicketCancellation $cancellation): BusTicketCancellation
    {
        $model = $cancellation->id === null
            ? new BusTicketCancellationModel
            : BusTicketCancellationModel::query()
                ->where('company_id', $cancellation->companyId)
                ->findOrFail($cancellation->id);

        $this->mapper->fillModel($cancellation, $model)->save();

        return $this->find($model->id, $model->company_id)
            ?? throw DomainException::because('No se pudo recuperar la cancelación guardada.');
    }

    /**
     * @return array{
     *     total: int,
     *     cancelled: int,
     *     not_cancelled: int,
     *     pending: int,
     *     total_amount_cents: int
     * }
     */
    public function summary(int $companyId, BusTicketCancellationFilters $filters): array
    {
        $base = $this->filteredQuery($companyId, $filters);

        return [
            'total' => (clone $base)->count(),
            'cancelled' => (clone $base)->where('result', BusTicketCancellationResult::Cancelled->value)->count(),
            'not_cancelled' => (clone $base)->where('result', BusTicketCancellationResult::NotCancelled->value)->count(),
            'pending' => (clone $base)->where('result', BusTicketCancellationResult::Pending->value)->count(),
            'total_amount_cents' => (int) (clone $base)->sum('amount_cents'),
        ];
    }

    /**
     * @return iterable<int, BusTicketCancellation>
     */
    public function export(int $companyId, BusTicketCancellationFilters $filters): iterable
    {
        $query = $this->filteredQuery($companyId, $filters)
            ->with(['operator', 'supportTicket'])
            ->orderBy('id');

        foreach ($query->lazyById(500) as $model) {
            yield $this->mapper->toDomain($model);
        }
    }

    /**
     * @return Builder<BusTicketCancellationModel>
     */
    private function filteredQuery(int $companyId, BusTicketCancellationFilters $filters): Builder
    {
        return BusTicketCancellationModel::query()
            ->where('company_id', $companyId)
            ->when(filled($filters->term), function (Builder $query) use ($filters): void {
                $term = $filters->term;

                $query->where(function (Builder $query) use ($term): void {
                    $query->where('ticket_number', 'like', "%{$term}%")
                        ->orWhere('authorization_reference', 'like', "%{$term}%")
                        ->orWhere('reason', 'like', "%{$term}%")
                        ->orWhereHas('supportTicket', function (Builder $query) use ($term): void {
                            $query->where('folio', 'like', "%{$term}%")
                                ->orWhere('subject', 'like', "%{$term}%");
                        });
                });
            })
            ->when($filters->result !== null, fn (Builder $query) => $query->where('result', $filters->result->value))
            ->when($filters->from !== null, fn (Builder $query) => $query->whereDate('requested_at', '>=', $filters->from->format('Y-m-d')))
            ->when($filters->to !== null, fn (Builder $query) => $query->whereDate('requested_at', '<=', $filters->to->format('Y-m-d')))
            ->when($filters->supportTicketId !== null, fn (Builder $query) => $query->where('support_ticket_id', $filters->supportTicketId));
    }
}
