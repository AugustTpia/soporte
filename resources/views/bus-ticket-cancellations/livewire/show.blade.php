<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
            <div class="mb-2 flex items-center gap-2 text-xs text-zinc-400">
                <a href="{{ route('bus-ticket-cancellations.index') }}" class="hover:text-royal-600" wire:navigate>Cancelación de boletos</a>
                <span>/</span>
                <span>{{ $cancellation->ticketNumber }}</span>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <h1 class="page-title">{{ $cancellation->ticketNumber }}</h1>
                <flux:badge :color="$cancellation->result->color()" size="sm">
                    {{ $cancellation->result->label() }}
                </flux:badge>
            </div>

            <p class="page-subtitle">
                {{ $companyName }} · Registrado {{ $cancellation->createdAt?->format('d/m/Y H:i') ?? '—' }}
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            @if($canManage)
                <flux:button :href="route('bus-ticket-cancellations.edit', $cancellation->id)" variant="primary" icon="pencil-square" wire:navigate>
                    Editar
                </flux:button>
            @endif
            <flux:button :href="route('bus-ticket-cancellations.index')" variant="ghost" wire:navigate>
                Volver
            </flux:button>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(320px,0.8fr)]">
        <div class="space-y-6">
            <section class="surface-card p-5 sm:p-6">
                <h2 class="text-sm font-semibold text-navy-900 dark:text-zinc-100">Motivo</h2>
                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-zinc-700 dark:text-zinc-300">{{ $cancellation->reason }}</p>
            </section>

            @if($cancellation->evidenceNotes)
                <section class="surface-card p-5 sm:p-6">
                    <h2 class="text-sm font-semibold text-navy-900 dark:text-zinc-100">Notas de evidencia</h2>
                    <p class="mt-3 whitespace-pre-line text-sm leading-6 text-zinc-700 dark:text-zinc-300">{{ $cancellation->evidenceNotes }}</p>
                </section>
            @endif

            @livewire(\App\BusTicketCancellations\Presentation\Livewire\BusTicketCancellationAttachments::class, [
                'cancellationId' => $cancellation->id,
            ], key('cancellation-attachments-'.$cancellation->id))
        </div>

        <div class="space-y-6">
            <section class="surface-card p-5">
                <h2 class="font-semibold text-navy-900 dark:text-zinc-100">Datos de la cancelación</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-zinc-500 dark:text-zinc-400">Ticket de soporte</dt>
                        <dd class="text-right text-zinc-700 dark:text-zinc-300">
                            @if($cancellation->supportTicketId !== null && $cancellation->supportTicketFolio !== null)
                                <a href="{{ route('tickets.show', $cancellation->supportTicketId) }}" class="font-medium text-royal-600 hover:underline" wire:navigate>
                                    {{ $cancellation->supportTicketFolio }}
                                </a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-zinc-500 dark:text-zinc-400">Fecha original del boleto</dt>
                        <dd class="text-right text-zinc-700 dark:text-zinc-300">{{ $cancellation->originalTicketAt?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-zinc-500 dark:text-zinc-400">Solicitud</dt>
                        <dd class="text-right text-zinc-700 dark:text-zinc-300">{{ $cancellation->requestedAt->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-zinc-500 dark:text-zinc-400">Ejecución</dt>
                        <dd class="text-right text-zinc-700 dark:text-zinc-300">{{ $cancellation->executedAt?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-zinc-500 dark:text-zinc-400">Importe</dt>
                        <dd class="text-right font-medium text-navy-900 dark:text-zinc-100">
                            {{ $cancellation->amountCents === null ? '—' : '$'.number_format($cancellation->amountCents / 100, 2) }}
                        </dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-zinc-500 dark:text-zinc-400">Operador</dt>
                        <dd class="text-right text-zinc-700 dark:text-zinc-300">{{ $cancellation->operatorName ?: '—' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-zinc-500 dark:text-zinc-400">Autorización</dt>
                        <dd class="text-right text-zinc-700 dark:text-zinc-300">{{ $cancellation->authorizationReference ?: '—' }}</dd>
                    </div>
                </dl>
            </section>
        </div>
    </div>
</div>
