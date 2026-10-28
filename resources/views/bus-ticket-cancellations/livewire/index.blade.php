<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="page-title">Cancelación de boletos</h1>
            <p class="page-subtitle">
                Registro y reporte de cancelaciones de {{ $companyName ?: 'la empresa' }}.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <flux:button :href="$exportUrl" variant="ghost" icon="arrow-down-tray">
                Exportar CSV
            </flux:button>
            @if($canManage)
                <flux:button :href="route('bus-ticket-cancellations.create')" variant="primary" icon="plus" wire:navigate>
                    Nueva cancelación
                </flux:button>
            @endif
        </div>
    </div>

    <div class="surface-card p-4">
        <div class="grid gap-3 lg:grid-cols-[1fr_180px_170px_170px_auto]">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Buscar boleto, autorización, motivo o ticket..."
                aria-label="Buscar cancelaciones"
            />

            <flux:select wire:model.live="result" aria-label="Filtrar por resultado">
                <option value="">Todos los resultados</option>
                @foreach($resultOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </flux:select>

            <flux:input wire:model.live="from" type="date" label="Desde" aria-label="Fecha inicial" />
            <flux:input wire:model.live="to" type="date" label="Hasta" aria-label="Fecha final" />

            <div class="flex items-end gap-2">
                <flux:button size="sm" variant="subtle" wire:click="setCurrentFortnight">Quincena</flux:button>
                <flux:button size="sm" variant="subtle" wire:click="setCurrentMonth">Mes</flux:button>
            </div>
        </div>

        @if($search !== '' || $result !== '' || $from !== '' || $to !== '')
            <div class="mt-3 flex justify-end">
                <flux:button size="sm" variant="subtle" icon="x-mark" wire:click="clearFilters">
                    Limpiar filtros
                </flux:button>
            </div>
        @endif
    </div>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div class="surface-card p-5">
            <p class="text-xs font-medium uppercase tracking-[0.12em] text-zinc-500 dark:text-zinc-400">Registros</p>
            <p class="mt-2 text-3xl font-semibold tracking-tight text-navy-900 dark:text-zinc-100">{{ $summary['total'] }}</p>
        </div>
        <div class="surface-card p-5">
            <p class="text-xs font-medium uppercase tracking-[0.12em] text-zinc-500 dark:text-zinc-400">Cancelados</p>
            <p class="mt-2 text-3xl font-semibold tracking-tight text-emerald-600 dark:text-emerald-300">{{ $summary['cancelled'] }}</p>
        </div>
        <div class="surface-card p-5">
            <p class="text-xs font-medium uppercase tracking-[0.12em] text-zinc-500 dark:text-zinc-400">No cancelados</p>
            <p class="mt-2 text-3xl font-semibold tracking-tight text-rose-600 dark:text-rose-300">{{ $summary['not_cancelled'] }}</p>
        </div>
        <div class="surface-card p-5">
            <p class="text-xs font-medium uppercase tracking-[0.12em] text-zinc-500 dark:text-zinc-400">Pendientes</p>
            <p class="mt-2 text-3xl font-semibold tracking-tight text-amber-600 dark:text-amber-300">{{ $summary['pending'] }}</p>
        </div>
        <div class="surface-card p-5">
            <p class="text-xs font-medium uppercase tracking-[0.12em] text-zinc-500 dark:text-zinc-400">Importe total</p>
            <p class="mt-2 text-2xl font-semibold tracking-tight text-navy-900 dark:text-zinc-100">
                ${{ number_format($summary['total_amount_cents'] / 100, 2) }}
            </p>
            <p class="text-xs text-zinc-400">MXN</p>
        </div>
    </section>

    <div class="surface-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800 text-sm">
                <thead class="bg-zinc-50/80 dark:bg-zinc-800/60 text-left text-[11px] uppercase tracking-[0.12em] text-zinc-500 dark:text-zinc-400">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Boleto</th>
                        <th class="px-5 py-3 font-semibold">Ticket de soporte</th>
                        <th class="px-5 py-3 font-semibold">Solicitud</th>
                        <th class="px-5 py-3 font-semibold">Ejecución</th>
                        <th class="px-5 py-3 font-semibold">Importe</th>
                        <th class="px-5 py-3 font-semibold">Operador</th>
                        <th class="px-5 py-3 font-semibold">Resultado</th>
                        <th class="px-5 py-3 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse($cancellations as $cancellation)
                        <tr wire:key="cancellation-{{ $cancellation->id }}" class="transition hover:bg-royal-50/40 dark:hover:bg-zinc-800/60">
                            <td class="px-5 py-4">
                                <a href="{{ route('bus-ticket-cancellations.show', $cancellation->id) }}" class="font-semibold text-navy-900 hover:text-royal-600 dark:text-zinc-100 dark:hover:text-royal-300" wire:navigate>
                                    {{ $cancellation->ticketNumber }}
                                </a>
                                @if($cancellation->originalTicketAt)
                                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                        Boleto original: {{ $cancellation->originalTicketAt->format('d/m/Y H:i') }}
                                    </p>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-zinc-600 dark:text-zinc-400">
                                {{ $cancellation->supportTicketFolio ?: '—' }}
                            </td>
                            <td class="px-5 py-4 text-zinc-600 dark:text-zinc-400">
                                {{ $cancellation->requestedAt->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-5 py-4 text-zinc-600 dark:text-zinc-400">
                                {{ $cancellation->executedAt?->format('d/m/Y H:i') ?? '—' }}
                            </td>
                            <td class="px-5 py-4 text-zinc-600 dark:text-zinc-400">
                                {{ $cancellation->amountCents === null ? '—' : '$'.number_format($cancellation->amountCents / 100, 2) }}
                            </td>
                            <td class="px-5 py-4 text-zinc-600 dark:text-zinc-400">
                                {{ $cancellation->operatorName ?: '—' }}
                            </td>
                            <td class="px-5 py-4">
                                <flux:badge :color="$cancellation->result->color()" size="sm">
                                    {{ $cancellation->result->label() }}
                                </flux:badge>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    <flux:button
                                        size="sm"
                                        variant="subtle"
                                        icon="eye"
                                        :href="route('bus-ticket-cancellations.show', $cancellation->id)"
                                        wire:navigate
                                        aria-label="Ver {{ $cancellation->ticketNumber }}"
                                    />
                                    @if($canManage)
                                        <flux:button
                                            size="sm"
                                            variant="subtle"
                                            icon="pencil-square"
                                            :href="route('bus-ticket-cancellations.edit', $cancellation->id)"
                                            wire:navigate
                                            aria-label="Editar {{ $cancellation->ticketNumber }}"
                                        />
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-14 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <span class="flex size-12 items-center justify-center rounded-2xl bg-royal-50 dark:bg-royal-600/20 text-royal-700 dark:text-royal-300">
                                        <flux:icon.ticket class="size-6" />
                                    </span>
                                    <p class="mt-4 font-medium text-navy-900 dark:text-zinc-100">Sin cancelaciones registradas</p>
                                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                        Ajusta el rango de fechas o registra una nueva cancelación.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($cancellations->hasPages())
            <div class="border-t border-zinc-100 dark:border-zinc-800 px-5 py-4">
                <flux:pagination :paginator="$cancellations" />
            </div>
        @endif
    </div>
</div>
