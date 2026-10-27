<?php

namespace App\Auditing\Presentation\Livewire;

use App\Auditing\Application\UseCases\SearchAuditLogs;
use App\Auditing\Infrastructure\Persistence\Eloquent\Models\AuditLogModel;
use App\Shared\Application\Contracts\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Auditoría')]
class AuditIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $event = '';

    public string $from = '';

    public string $to = '';

    public function mount(): void
    {
        $this->authorize('viewAny', AuditLogModel::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedEvent(): void
    {
        $this->resetPage();
    }

    public function updatedFrom(): void
    {
        $this->resetPage();
    }

    public function updatedTo(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'event', 'from', 'to');
        $this->resetPage();
    }

    public function render(): View
    {
        $companyId = app(TenantContext::class)->companyId();

        $result = app(SearchAuditLogs::class)->execute(
            $companyId,
            $this->search !== '' ? $this->search : null,
            $this->event !== '' ? $this->event : null,
            $this->from !== '' ? $this->from : null,
            $this->to !== '' ? $this->to : null,
            $this->getPage(),
            15,
        );

        $logs = new LengthAwarePaginator(
            $result->items,
            $result->total,
            $result->perPage,
            $result->currentPage,
            ['path' => route('audit.index')],
        );

        return view('auditing.livewire.index', [
            'logs' => $logs,
            'global' => $companyId === null,
            'events' => [
                'company.created' => 'Empresa creada',
                'company.updated' => 'Empresa actualizada',
                'company.status_changed' => 'Estado de empresa',
                'department.created' => 'Departamento creado',
                'department.updated' => 'Departamento actualizado',
                'department.status_changed' => 'Estado de departamento',
                'user.created' => 'Usuario creado',
                'user.updated' => 'Usuario actualizado',
                'user.status_changed' => 'Estado de usuario',
            ],
        ]);
    }
}
