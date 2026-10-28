<?php

namespace App\Support\Presentation\Livewire\Tickets;

use App\Identity\Domain\Enums\RoleName;
use App\Identity\Domain\Repositories\UserRepository;
use App\Shared\Application\Contracts\TenantContext;
use App\Shared\Domain\Exceptions\DomainException;
use App\Support\Application\UseCases\AddTicketComment;
use App\Support\Application\UseCases\AssignTicket;
use App\Support\Application\UseCases\ChangeTicketStatus;
use App\Support\Application\UseCases\UnassignTicket;
use App\Support\Domain\Enums\TicketStatus;
use App\Support\Domain\Repositories\TicketRepository;
use App\Support\Infrastructure\Persistence\Eloquent\Models\SupportTicketModel;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Ticket')]
class TicketShow extends Component
{
    public ?int $ticketId = null;

    public ?int $companyId = null;

    public string $comment = '';

    public string $assignUserId = '';

    public string $assignmentReason = '';

    public string $unassignReason = '';

    public string $statusReason = '';

    public function mount(SupportTicketModel $ticket): void
    {
        $this->companyId = app(TenantContext::class)->companyId();

        if ($this->companyId === null) {
            $this->redirectRoute('companies.index', navigate: true);

            return;
        }

        if ((int) $ticket->company_id !== $this->companyId) {
            abort(404);
        }

        $this->authorize('view', $ticket);

        $this->ticketId = $ticket->id;
    }

    public function assign(): void
    {
        $ticket = $this->currentModel();
        $this->authorize('assign', $ticket);

        $validated = $this->validate([
            'assignUserId' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('company_id', $this->companyId),
            ],
            'assignmentReason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            app(AssignTicket::class)->execute(
                companyId: (int) $this->companyId,
                ticketId: (int) $this->ticketId,
                userId: (int) $validated['assignUserId'],
                actorId: (int) auth()->id(),
                reason: $validated['assignmentReason'] ?? null,
            );

            $this->reset('assignUserId', 'assignmentReason');
            Flux::toast(variant: 'success', text: 'Técnico asignado correctamente.');
        } catch (DomainException $exception) {
            $this->addError('assignment', $exception->getMessage());
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }
    }

    public function unassign(int $userId): void
    {
        $ticket = $this->currentModel();
        $this->authorize('assign', $ticket);

        try {
            app(UnassignTicket::class)->execute(
                companyId: (int) $this->companyId,
                ticketId: (int) $this->ticketId,
                userId: $userId,
                actorId: (int) auth()->id(),
                reason: $this->unassignReason,
            );

            $this->reset('unassignReason');
            Flux::toast(variant: 'success', text: 'Técnico retirado del ticket.');
        } catch (DomainException $exception) {
            $this->addError('assignment', $exception->getMessage());
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }
    }

    public function changeStatus(string $status): void
    {
        $target = TicketStatus::tryFrom($status);

        if ($target === null) {
            $this->addError('status', 'El estado seleccionado no es válido.');

            return;
        }

        $ticket = $this->currentModel();

        if ($target === TicketStatus::Cancelled) {
            $this->authorize('cancel', $ticket);
        } else {
            $this->authorize('update', $ticket);
        }

        $validated = $this->validate([
            'statusReason' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            app(ChangeTicketStatus::class)->execute(
                companyId: (int) $this->companyId,
                ticketId: (int) $this->ticketId,
                target: $target,
                actorId: (int) auth()->id(),
                reason: $validated['statusReason'] ?? null,
            );

            $this->reset('statusReason');
            Flux::toast(variant: 'success', text: 'Estado del ticket actualizado.');
        } catch (DomainException $exception) {
            $this->addError('status', $exception->getMessage());
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }
    }

    public function addComment(): void
    {
        $ticket = $this->currentModel();
        $this->authorize('update', $ticket);

        $validated = $this->validate([
            'comment' => ['required', 'string', 'max:5000'],
        ]);

        try {
            app(AddTicketComment::class)->execute(
                companyId: (int) $this->companyId,
                ticketId: (int) $this->ticketId,
                body: $validated['comment'],
                actorId: (int) auth()->id(),
            );

            $this->reset('comment');
            Flux::toast(variant: 'success', text: 'Comentario agregado.');
        } catch (DomainException $exception) {
            $this->addError('comment', $exception->getMessage());
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }
    }

    public function render(): View
    {
        $model = $this->currentModel();
        $ticket = app(TicketRepository::class)->find((int) $this->ticketId, (int) $this->companyId);

        if ($ticket === null) {
            abort(404);
        }

        $canManage = auth()->user()->can('update', $model);
        $canAssign = auth()->user()->can('assign', $model);
        $canCancel = auth()->user()->can('cancel', $model);

        $allowedTransitions = $canManage
            ? array_values(array_filter(
                $ticket->status->allowedTransitions(),
                fn (TicketStatus $status) => ! in_array($status, [TicketStatus::Open, TicketStatus::Assigned], true)
                    && ($status !== TicketStatus::Cancelled || $canCancel),
            ))
            : [];

        $activeUserIds = array_map(
            fn ($assignment) => $assignment->userId,
            $ticket->activeAssignments(),
        );

        $assigneeOptions = collect(app(UserRepository::class)->forCompany((int) $this->companyId))
            ->filter(fn ($user) => $user->isActive()
                && ! in_array($user->id, $activeUserIds, true)
                && array_intersect($user->roles, [
                    RoleName::CompanyAdmin->value,
                    RoleName::ServiceManager->value,
                    RoleName::Engineer->value,
                    RoleName::Technician->value,
                ]) !== [])
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
            ])
            ->values()
            ->all();

        return view('tickets.livewire.show', [
            'ticket' => $ticket,
            'canManage' => $canManage,
            'canAssign' => $canAssign,
            'canCancel' => $canCancel,
            'canChangeAssignments' => $canAssign
                && ! in_array($ticket->status, [TicketStatus::Resolved, TicketStatus::Closed, TicketStatus::Cancelled], true),
            'allowedTransitions' => $allowedTransitions,
            'assigneeOptions' => $assigneeOptions,
        ]);
    }

    private function currentModel(): SupportTicketModel
    {
        if ($this->companyId === null || $this->ticketId === null) {
            abort(403);
        }

        return SupportTicketModel::query()
            ->where('company_id', $this->companyId)
            ->findOrFail($this->ticketId);
    }
}
