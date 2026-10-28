<?php

namespace App\Policies;

use App\BusTicketCancellations\Infrastructure\Persistence\Eloquent\Models\BusTicketCancellationModel;
use App\Companies\Domain\Enums\CompanyFeature;
use App\Companies\Domain\Repositories\CompanyRepository;
use App\Identity\Infrastructure\Persistence\Eloquent\User;
use App\Shared\Application\Contracts\TenantContext;

final class BusTicketCancellationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('bus_ticket_cancellations.view') && $this->featureEnabled();
    }

    public function view(User $user, BusTicketCancellationModel $cancellation): bool
    {
        return $user->can('bus_ticket_cancellations.view')
            && $user->company_id === $cancellation->company_id
            && $this->featureEnabled();
    }

    public function create(User $user): bool
    {
        return $user->can('bus_ticket_cancellations.manage') && $this->featureEnabled();
    }

    public function update(User $user, BusTicketCancellationModel $cancellation): bool
    {
        return $user->can('bus_ticket_cancellations.manage')
            && $user->company_id === $cancellation->company_id
            && $this->featureEnabled();
    }

    public function delete(User $user, BusTicketCancellationModel $cancellation): bool
    {
        return false;
    }

    private function featureEnabled(): bool
    {
        $companyId = app(TenantContext::class)->companyId();

        if ($companyId === null) {
            return false;
        }

        $company = app(CompanyRepository::class)->find($companyId);

        return $company?->hasFeature(CompanyFeature::BusTicketCancellations) ?? false;
    }
}
