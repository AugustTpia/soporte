<?php

namespace App\Companies\Infrastructure\Queries;

use App\Accounts\Domain\Enums\ServiceAccountStatus;
use App\Accounts\Infrastructure\Persistence\Eloquent\Models\ServiceAccountModel;
use App\Assets\Domain\Enums\AssetStatus;
use App\Assets\Infrastructure\Persistence\Eloquent\Models\AssetModel;
use App\Auditing\Infrastructure\Persistence\Eloquent\Models\AuditLogModel;
use App\BusTicketCancellations\Infrastructure\Persistence\Eloquent\Models\BusTicketCancellationModel;
use App\Companies\Application\Contracts\CompanyOverviewQuery;
use App\Companies\Application\DTOs\CompanyOverview;
use App\Contacts\Infrastructure\Persistence\Eloquent\Models\ClientContactModel;
use App\Locations\Infrastructure\Persistence\Eloquent\Models\LocationModel;
use App\Maintenance\Domain\Enums\MaintenancePlanStatus;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenanceExecutionModel;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenancePlanModel;
use App\Support\Domain\Enums\TicketStatus;
use App\Support\Infrastructure\Persistence\Eloquent\Models\SupportTicketModel;

final class EloquentCompanyOverviewQuery implements CompanyOverviewQuery
{
    public function execute(int $companyId, array $modules): CompanyOverview
    {
        $counts = [];

        if ($this->enabled('contacts', $modules)) {
            $counts['contacts'] = ClientContactModel::query()
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->count();
        }

        if ($this->enabled('locations', $modules)) {
            $counts['locations'] = LocationModel::query()
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->count();
        }

        if ($this->enabled('assets', $modules)) {
            $counts['assets'] = AssetModel::query()
                ->where('company_id', $companyId)
                ->where('status', AssetStatus::Active->value)
                ->count();
        }

        if ($this->enabled('accounts', $modules)) {
            $counts['accounts'] = ServiceAccountModel::query()
                ->where('company_id', $companyId)
                ->where('status', ServiceAccountStatus::Active->value)
                ->count();
        }

        if ($this->enabled('tickets', $modules)) {
            $counts['tickets'] = SupportTicketModel::query()
                ->where('company_id', $companyId)
                ->whereIn('status', [
                    TicketStatus::Open->value,
                    TicketStatus::Assigned->value,
                    TicketStatus::InProgress->value,
                    TicketStatus::Waiting->value,
                ])
                ->count();
        }

        if ($this->enabled('maintenance', $modules)) {
            $counts['maintenance'] = MaintenancePlanModel::query()
                ->where('company_id', $companyId)
                ->where('status', MaintenancePlanStatus::Active->value)
                ->whereBetween('next_due_at', [now(), now()->addDays(30)])
                ->count();
        }

        if ($this->enabled('cancellations', $modules)) {
            $counts['cancellations'] = BusTicketCancellationModel::query()
                ->where('company_id', $companyId)
                ->where('requested_at', '>=', now()->subDays(30))
                ->count();
        }

        return new CompanyOverview(
            counts: $counts,
            recentTickets: $this->enabled('tickets', $modules) ? $this->recentTickets($companyId) : [],
            recentMaintenance: $this->enabled('maintenance', $modules) ? $this->recentMaintenance($companyId) : [],
            recentCancellations: $this->enabled('cancellations', $modules) ? $this->recentCancellations($companyId) : [],
            recentActivity: $this->enabled('audit', $modules) ? $this->recentActivity($companyId) : [],
        );
    }

    /**
     * @param  array<int, string>  $modules
     */
    private function enabled(string $module, array $modules): bool
    {
        return in_array($module, $modules, true);
    }

    /**
     * @return array<int, array{id: int, folio: string|null, subject: string, status: string, opened_at: string}>
     */
    private function recentTickets(int $companyId): array
    {
        return SupportTicketModel::query()
            ->where('company_id', $companyId)
            ->orderByDesc('opened_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (SupportTicketModel $ticket) => [
                'id' => (int) $ticket->id,
                'folio' => $ticket->folio,
                'subject' => $ticket->subject,
                'status' => $ticket->status,
                'opened_at' => $ticket->opened_at->format('d/m/Y H:i'),
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: int, title: string, target: string, status: string, scheduled_at: string}>
     */
    private function recentMaintenance(int $companyId): array
    {
        return MaintenanceExecutionModel::query()
            ->where('company_id', $companyId)
            ->orderByDesc('scheduled_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (MaintenanceExecutionModel $execution) => [
                'id' => (int) $execution->id,
                'title' => $execution->target_label,
                'target' => $execution->target_label,
                'status' => $execution->status,
                'scheduled_at' => $execution->scheduled_at->format('d/m/Y H:i'),
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: int, ticket_number: string, result: string, requested_at: string}>
     */
    private function recentCancellations(int $companyId): array
    {
        return BusTicketCancellationModel::query()
            ->where('company_id', $companyId)
            ->orderByDesc('requested_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (BusTicketCancellationModel $cancellation) => [
                'id' => (int) $cancellation->id,
                'ticket_number' => $cancellation->ticket_number,
                'result' => $cancellation->result,
                'requested_at' => $cancellation->requested_at->format('d/m/Y H:i'),
            ])
            ->all();
    }

    /**
     * @return array<int, array{event: string, description: string, actor: string|null, created_at: string}>
     */
    private function recentActivity(int $companyId): array
    {
        return AuditLogModel::query()
            ->with('actor')
            ->where('company_id', $companyId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get()
            ->map(fn (AuditLogModel $log) => [
                'event' => $log->event,
                'description' => $log->description,
                'actor' => $log->actor?->name,
                'created_at' => $log->created_at->format('d/m/Y H:i'),
            ])
            ->all();
    }
}
