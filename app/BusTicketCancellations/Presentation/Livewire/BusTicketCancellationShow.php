<?php

namespace App\BusTicketCancellations\Presentation\Livewire;

use App\BusTicketCancellations\Domain\Repositories\BusTicketCancellationRepository;
use App\BusTicketCancellations\Infrastructure\Persistence\Eloquent\Models\BusTicketCancellationModel;
use App\Companies\Domain\Repositories\CompanyRepository;
use App\Shared\Application\Contracts\TenantContext;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Detalle de cancelación')]
class BusTicketCancellationShow extends Component
{
    public ?int $cancellationId = null;

    public ?int $companyId = null;

    public function mount(BusTicketCancellationModel $cancellation): void
    {
        $this->companyId = app(TenantContext::class)->companyId();

        if ($this->companyId === null) {
            $this->redirectRoute('companies.index', navigate: true);

            return;
        }

        if ((int) $cancellation->company_id !== $this->companyId) {
            abort(404);
        }

        $this->authorize('view', $cancellation);

        $this->cancellationId = $cancellation->id;
    }

    public function render(): View
    {
        if ($this->cancellationId === null || $this->companyId === null) {
            abort(404);
        }

        $cancellation = app(BusTicketCancellationRepository::class)->find(
            $this->cancellationId,
            $this->companyId,
        );

        if ($cancellation === null) {
            abort(404);
        }

        $company = app(CompanyRepository::class)->find($this->companyId);

        if ($company === null) {
            abort(404);
        }

        return view('bus-ticket-cancellations.livewire.show', [
            'cancellation' => $cancellation,
            'companyName' => $company->name,
            'canManage' => auth()->user()->can('bus_ticket_cancellations.manage'),
        ]);
    }
}
