<?php

namespace App\Presentation\Livewire;

use App\Auditing\Application\UseCases\SearchAuditLogs;
use App\Auditing\Domain\Repositories\AuditRepository;
use App\Companies\Domain\Enums\CompanyStatus;
use App\Companies\Domain\Repositories\CompanyRepository;
use App\Departments\Domain\Repositories\DepartmentRepository;
use App\Identity\Domain\Enums\UserStatus;
use App\Identity\Domain\Repositories\UserRepository;
use App\Maintenance\Application\UseCases\CountDashboardMaintenance;
use App\Shared\Application\Contracts\TenantContext;
use App\Support\Application\UseCases\CountDashboardTickets;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Panel')]
class Dashboard extends Component
{
    public function render(): View
    {
        $tenant = app(TenantContext::class);
        $companyId = $tenant->companyId();
        $company = $companyId === null ? null : app(CompanyRepository::class)->find($companyId);

        if ($companyId !== null && $company === null) {
            $tenant->clear();

            $this->redirectRoute('dashboard', navigate: true);

            return view('identity.livewire.dashboard.index', [
                'company' => null,
                'stats' => [],
                'recentCompanies' => [],
                'recentUsers' => [],
                'recentAudit' => [],
                'canViewAudit' => false,
                'ticketStats' => [],
                'maintenanceStats' => [],
            ]);
        }

        if ($company !== null) {
            $users = app(UserRepository::class);
            $departments = app(DepartmentRepository::class);
            $audit = app(AuditRepository::class);
            $canViewAudit = auth()->user()->can('audit.view');

            $stats = [
                'users_total' => $users->count($companyId),
                'users_active' => $users->count($companyId, UserStatus::Active),
                'departments_total' => $departments->count($companyId),
                'departments_active' => $departments->count($companyId, true),
            ];

            if ($canViewAudit) {
                $stats['audit_total'] = $audit->count($companyId);
            }

            $ticketStats = auth()->user()->can('tickets.view')
                ? app(CountDashboardTickets::class)->execute($companyId, (int) auth()->id())
                : [];

            $maintenanceStats = auth()->user()->can('maintenance.view')
                ? app(CountDashboardMaintenance::class)->execute($companyId)
                : [];

            return view('identity.livewire.dashboard.index', [
                'company' => $company,
                'stats' => $stats,
                'recentCompanies' => [],
                'recentUsers' => $users->recent($companyId, 5),
                'recentAudit' => $canViewAudit
                    ? app(SearchAuditLogs::class)->execute($companyId, null, null, null, null, 1, 5)->items
                    : [],
                'canViewAudit' => $canViewAudit,
                'ticketStats' => $ticketStats,
                'maintenanceStats' => $maintenanceStats,
            ]);
        }

        $companies = app(CompanyRepository::class);
        $canViewAudit = auth()->user()->can('audit.view');

        return view('identity.livewire.dashboard.index', [
            'company' => null,
            'stats' => [
                'companies_total' => $companies->count(),
                'companies_active' => $companies->count(CompanyStatus::Active),
                'users_total' => app(UserRepository::class)->count(null),
            ],
            'recentCompanies' => $companies->recent(5),
            'recentUsers' => [],
            'recentAudit' => $canViewAudit
                ? app(SearchAuditLogs::class)->execute(null, null, null, null, null, 1, 5)->items
                : [],
            'canViewAudit' => $canViewAudit,
            'ticketStats' => [],
            'maintenanceStats' => [],
        ]);
    }
}
