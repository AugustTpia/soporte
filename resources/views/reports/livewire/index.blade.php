<div class="space-y-6">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <h1 class="page-title">Reportes operativos</h1>
            <p class="page-subtitle">
                {{ $reportDescription }} {{ $companyName ? '· '.$companyName : '' }}
            </p>
        </div>

        @if($exportUrls !== [])
            <div class="flex flex-wrap gap-2">
                <flux:button :href="$exportUrls['csv']" variant="subtle" icon="arrow-down-tray">
                    Exportar CSV
                </flux:button>
                <flux:button :href="$exportUrls['pdf']" variant="primary" icon="document-arrow-down">
                    Exportar PDF
                </flux:button>
            </div>
        @endif
    </div>

    <nav class="flex flex-wrap gap-2 rounded-2xl border border-zinc-200 bg-white p-2 shadow-sm dark:border-zinc-800 dark:bg-zinc-900" aria-label="Tipos de reporte">
        @foreach($availableReports as $key => $label)
            <button
                type="button"
                wire:click="switchReport('{{ $key }}')"
                wire:loading.attr="disabled"
                @if($report === $key) aria-current="page" @endif
                class="rounded-xl px-4 py-2 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-royal-500 focus-visible:ring-offset-2 {{ $report === $key ? 'bg-royal-600 text-white shadow-sm' : 'text-zinc-600 hover:bg-royal-50 hover:text-royal-700 dark:text-zinc-300 dark:hover:bg-royal-600/20 dark:hover:text-royal-200' }}"
            >
                {{ $label }}
            </button>
        @endforeach
    </nav>

    <div class="surface-card p-4">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <flux:input
                type="date"
                wire:model.live="from"
                label="Desde"
                aria-label="Fecha inicial del reporte"
            />
            <flux:input
                type="date"
                wire:model.live="to"
                label="Hasta"
                aria-label="Fecha final del reporte"
            />

            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                label="Buscar"
                placeholder="{{ $report === 'tickets' ? 'Folio, asunto o solicitante...' : ($report === 'maintenance' ? 'Objetivo, plan, hallazgos o ticket...' : 'Boleto, ticket o motivo...') }}"
                aria-label="Buscar en el reporte"
            />

            @if($report !== 'cancellations')
                <flux:select wire:model.live="status" label="Estado" aria-label="Filtrar por estado">
                    <option value="">Todos los estados</option>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </flux:select>
            @endif

            @if($report === 'tickets')
                <flux:select wire:model.live="priority" label="Prioridad" aria-label="Filtrar por prioridad">
                    <option value="">Todas las prioridades</option>
                    @foreach($priorityOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="category" label="Categoría" aria-label="Filtrar por categoría">
                    <option value="">Todas las categorías</option>
                    @foreach($categoryOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </flux:select>
            @endif

            @if($report === 'maintenance')
                <flux:select wire:model.live="type" label="Tipo" aria-label="Filtrar por tipo de mantenimiento">
                    <option value="">Todos los tipos</option>
                    @foreach($typeOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="plan" label="Plan" aria-label="Filtrar por plan de mantenimiento">
                    <option value="">Todos los planes</option>
                    @foreach($planOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </flux:select>
            @endif

            @if($report === 'tickets' || $report === 'maintenance')
                <flux:select wire:model.live="technician" label="Técnico" aria-label="Filtrar por técnico">
                    <option value="">Todos los técnicos</option>
                    @foreach($technicianOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </flux:select>
            @endif

            @if($report === 'cancellations')
                <flux:select wire:model.live="result" label="Resultado" aria-label="Filtrar por resultado">
                    <option value="">Todos los resultados</option>
                    @foreach($resultOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="operator" label="Operador" aria-label="Filtrar por operador">
                    <option value="">Todos los operadores</option>
                    @foreach($technicianOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </flux:select>
            @endif
        </div>

        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                Periodo: <span class="font-semibold text-navy-900 dark:text-zinc-100">{{ $periodLabel ?: 'No disponible' }}</span>
            </p>

            @if($hasFilters)
                <flux:button size="sm" variant="subtle" icon="x-mark" wire:click="clearFilters">
                    Limpiar filtros
                </flux:button>
            @endif
        </div>

        @if($periodError)
            <div class="mt-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-200" role="alert">
                {{ $periodError }}
            </div>
        @endif
    </div>

    @if($metrics !== [])
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6" aria-label="Resumen del reporte">
            @foreach($metrics as $metric)
                @php
                    $toneClasses = match ($metric->tone) {
                        'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300',
                        'amber' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-300',
                        'rose' => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300',
                        'zinc' => 'border-zinc-200 bg-zinc-50 text-zinc-700 dark:border-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-300',
                        default => 'border-royal-200 bg-royal-50 text-royal-700 dark:border-royal-900/50 dark:bg-royal-950/30 dark:text-royal-300',
                    };
                @endphp
                <div class="surface-card border p-5 {{ $toneClasses }}">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] opacity-80">{{ $metric->label }}</p>
                    <p class="mt-2 text-3xl font-semibold tracking-tight">{{ $metric->value }}</p>
                    @if($metric->hint)
                        <p class="mt-1 text-xs opacity-80">{{ $metric->hint }}</p>
                    @endif
                </div>
            @endforeach
        </section>
    @endif

    <div class="surface-card overflow-hidden">
        <div class="overflow-x-auto">
            @if($report === 'tickets')
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                    <thead class="bg-zinc-50/80 text-left text-[11px] uppercase tracking-[0.12em] text-zinc-500 dark:bg-zinc-800/60 dark:text-zinc-400">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Fecha</th>
                            <th class="px-5 py-3 font-semibold">Folio</th>
                            <th class="px-5 py-3 font-semibold">Asunto</th>
                            <th class="px-5 py-3 font-semibold">Categoría</th>
                            <th class="px-5 py-3 font-semibold">Prioridad</th>
                            <th class="px-5 py-3 font-semibold">Estado</th>
                            <th class="px-5 py-3 font-semibold">Técnicos</th>
                            <th class="px-5 py-3 font-semibold">Resolución</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse($items as $ticket)
                            <tr wire:key="report-ticket-{{ $ticket->id }}" class="transition hover:bg-royal-50/40 dark:hover:bg-zinc-800/60">
                                <td class="whitespace-nowrap px-5 py-4 text-zinc-600 dark:text-zinc-400">{{ $ticket->openedAt->format('d/m/Y H:i') }}</td>
                                <td class="whitespace-nowrap px-5 py-4 font-semibold text-navy-900 dark:text-zinc-100">{{ $ticket->folio ?: '—' }}</td>
                                <td class="min-w-64 px-5 py-4">
                                    <p class="font-medium text-navy-900 dark:text-zinc-100">{{ $ticket->subject }}</p>
                                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $ticket->locationName ?: 'Sin ubicación' }}</p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-zinc-600 dark:text-zinc-400">{{ $ticket->category->label() }}</td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <flux:badge :color="$ticket->priority->color()" size="sm">{{ $ticket->priority->label() }}</flux:badge>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <flux:badge :color="$ticket->status->color()" size="sm">{{ $ticket->status->label() }}</flux:badge>
                                </td>
                                <td class="px-5 py-4 text-zinc-600 dark:text-zinc-400">{{ implode(', ', $ticket->technicians) ?: 'Sin asignar' }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-zinc-600 dark:text-zinc-400">
                                    {{ $ticket->resolvedAt?->format('d/m/Y H:i') ?? '—' }}
                                    @if($ticket->resolutionHours !== null)
                                        <p class="mt-0.5 text-xs text-zinc-400">{{ number_format($ticket->resolutionHours, 2) }} h</p>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-14 text-center">
                                    <div class="mx-auto flex max-w-sm flex-col items-center">
                                        <span class="flex size-12 items-center justify-center rounded-2xl bg-royal-50 text-royal-700 dark:bg-royal-600/20 dark:text-royal-300">
                                            <flux:icon.chart-bar class="size-6" />
                                        </span>
                                        <p class="mt-4 font-medium text-navy-900 dark:text-zinc-100">Sin tickets en el periodo</p>
                                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Ajusta el rango de fechas o los filtros.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @elseif($report === 'maintenance')
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                    <thead class="bg-zinc-50/80 text-left text-[11px] uppercase tracking-[0.12em] text-zinc-500 dark:bg-zinc-800/60 dark:text-zinc-400">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Programado</th>
                            <th class="px-5 py-3 font-semibold">Objetivo</th>
                            <th class="px-5 py-3 font-semibold">Plan</th>
                            <th class="px-5 py-3 font-semibold">Tipo</th>
                            <th class="px-5 py-3 font-semibold">Técnico</th>
                            <th class="px-5 py-3 font-semibold">Estado</th>
                            <th class="px-5 py-3 font-semibold">Cierre</th>
                            <th class="px-5 py-3 text-right font-semibold">Tiempo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse($items as $execution)
                            <tr wire:key="report-maintenance-{{ $execution->id }}" class="transition hover:bg-royal-50/40 dark:hover:bg-zinc-800/60">
                                <td class="whitespace-nowrap px-5 py-4 text-zinc-600 dark:text-zinc-400">{{ $execution->scheduledAt->format('d/m/Y H:i') }}</td>
                                <td class="min-w-56 px-5 py-4">
                                    <p class="font-medium text-navy-900 dark:text-zinc-100">{{ $execution->targetLabel }}</p>
                                    @if($execution->supportTicketFolio)
                                        <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Ticket {{ $execution->supportTicketFolio }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-zinc-600 dark:text-zinc-400">{{ $execution->planTitle ?: 'Sin plan' }}</td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <flux:badge :color="$execution->type->color()" size="sm">{{ $execution->type->label() }}</flux:badge>
                                </td>
                                <td class="px-5 py-4 text-zinc-600 dark:text-zinc-400">{{ $execution->technicianName ?: 'Sin técnico' }}</td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <flux:badge :color="$execution->status->color()" size="sm">{{ $execution->status->label() }}</flux:badge>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-zinc-600 dark:text-zinc-400">{{ $execution->completedAt?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-right text-zinc-600 dark:text-zinc-400">{{ $execution->timeSpentMinutes !== null ? $execution->timeSpentMinutes.' min' : '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-14 text-center">
                                    <div class="mx-auto flex max-w-sm flex-col items-center">
                                        <span class="flex size-12 items-center justify-center rounded-2xl bg-royal-50 text-royal-700 dark:bg-royal-600/20 dark:text-royal-300">
                                            <flux:icon.chart-bar class="size-6" />
                                        </span>
                                        <p class="mt-4 font-medium text-navy-900 dark:text-zinc-100">Sin mantenimientos en el periodo</p>
                                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Ajusta el rango de fechas o los filtros.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @else
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                    <thead class="bg-zinc-50/80 text-left text-[11px] uppercase tracking-[0.12em] text-zinc-500 dark:bg-zinc-800/60 dark:text-zinc-400">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Solicitado</th>
                            <th class="px-5 py-3 font-semibold">Boleto</th>
                            <th class="px-5 py-3 font-semibold">Ticket</th>
                            <th class="px-5 py-3 font-semibold">Operador</th>
                            <th class="px-5 py-3 font-semibold">Resultado</th>
                            <th class="px-5 py-3 text-right font-semibold">Importe</th>
                            <th class="px-5 py-3 font-semibold">Ejecutado</th>
                            <th class="px-5 py-3 font-semibold">Motivo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse($items as $cancellation)
                            <tr wire:key="report-cancellation-{{ $cancellation->id }}" class="transition hover:bg-royal-50/40 dark:hover:bg-zinc-800/60">
                                <td class="whitespace-nowrap px-5 py-4 text-zinc-600 dark:text-zinc-400">{{ $cancellation->requestedAt->format('d/m/Y H:i') }}</td>
                                <td class="whitespace-nowrap px-5 py-4 font-semibold text-navy-900 dark:text-zinc-100">{{ $cancellation->ticketNumber }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-zinc-600 dark:text-zinc-400">{{ $cancellation->supportTicketFolio ?: '—' }}</td>
                                <td class="px-5 py-4 text-zinc-600 dark:text-zinc-400">{{ $cancellation->operatorName }}</td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <flux:badge :color="$cancellation->result->color()" size="sm">{{ $cancellation->result->label() }}</flux:badge>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-right text-zinc-600 dark:text-zinc-400">{{ $cancellation->amountCents !== null ? '$'.number_format($cancellation->amountCents / 100, 2, '.', ',') : '—' }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-zinc-600 dark:text-zinc-400">{{ $cancellation->executedAt?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="min-w-56 px-5 py-4 text-zinc-600 dark:text-zinc-400">{{ $cancellation->reason }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-14 text-center">
                                    <div class="mx-auto flex max-w-sm flex-col items-center">
                                        <span class="flex size-12 items-center justify-center rounded-2xl bg-royal-50 text-royal-700 dark:bg-royal-600/20 dark:text-royal-300">
                                            <flux:icon.chart-bar class="size-6" />
                                        </span>
                                        <p class="mt-4 font-medium text-navy-900 dark:text-zinc-100">Sin cancelaciones en el periodo</p>
                                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Ajusta el rango de fechas o los filtros.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>

        @if($paginator->hasPages())
            <div class="border-t border-zinc-100 px-5 py-4 dark:border-zinc-800">
                <flux:pagination :paginator="$paginator" />
            </div>
        @endif
    </div>
</div>
