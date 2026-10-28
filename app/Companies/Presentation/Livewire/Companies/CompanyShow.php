<?php

namespace App\Companies\Presentation\Livewire\Companies;

use App\Companies\Application\Contracts\CompanyOverviewQuery;
use App\Companies\Domain\Enums\CompanyFeature;
use App\Companies\Domain\Repositories\CompanyRepository;
use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use App\Shared\Application\Contracts\TenantContext;
use App\Shared\Domain\Exceptions\DomainException;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Ficha del cliente')]
class CompanyShow extends Component
{
    public int $companyId;

    public function mount(CompanyModel $company): void
    {
        $tenant = app(TenantContext::class);

        if (! $tenant->isSuperAdmin() && $tenant->companyId() !== (int) $company->id) {
            abort(404);
        }

        $this->authorize('view', $company);

        if ($tenant->isSuperAdmin() && $tenant->companyId() !== (int) $company->id) {
            try {
                $tenant->setCompanyId((int) $company->id);
                Flux::toast(variant: 'success', text: 'Empresa activa actualizada.');
            } catch (DomainException) {
                abort(404);
            }
        }

        $this->companyId = (int) $company->id;
    }

    public function render(): View
    {
        $company = CompanyModel::query()->find($this->companyId);

        if ($company === null) {
            abort(404);
        }

        $tenant = app(TenantContext::class);

        if (! $tenant->isSuperAdmin() && $tenant->companyId() !== (int) $company->id) {
            abort(404);
        }

        $this->authorize('view', $company);

        $actor = auth()->user();
        $featureCancellations = $this->hasFeature($company, CompanyFeature::BusTicketCancellations);

        $modules = array_values(array_filter([
            $actor->can('contacts.view') ? 'contacts' : null,
            $actor->can('locations.view') ? 'locations' : null,
            $actor->can('assets.view') ? 'assets' : null,
            $actor->can('accounts.view') ? 'accounts' : null,
            $actor->can('tickets.view') ? 'tickets' : null,
            $actor->can('maintenance.view') ? 'maintenance' : null,
            $featureCancellations && $actor->can('bus_ticket_cancellations.view') ? 'cancellations' : null,
            $actor->can('audit.view') ? 'audit' : null,
        ]));

        $overview = app(CompanyOverviewQuery::class)->execute($this->companyId, $modules);

        $domainCompany = app(CompanyRepository::class)->find($this->companyId);

        if ($domainCompany === null) {
            abort(404);
        }

        return view('companies.livewire.companies.show', [
            'company' => $domainCompany,
            'overview' => $overview,
            'featureCancellations' => $featureCancellations,
            'canViewContacts' => $actor->can('contacts.view'),
            'canViewTickets' => $actor->can('tickets.view'),
            'canViewMaintenance' => $actor->can('maintenance.view'),
            'canViewCancellations' => $featureCancellations && $actor->can('bus_ticket_cancellations.view'),
            'canViewActivity' => $actor->can('audit.view'),
        ]);
    }

    private function hasFeature(CompanyModel $company, CompanyFeature $feature): bool
    {
        $settings = $company->settings ?? [];
        $features = $settings['features'] ?? [];

        return is_array($features) && (bool) ($features[$feature->value] ?? false);
    }
}
