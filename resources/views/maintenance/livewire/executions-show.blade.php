<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <div class="mb-2 flex flex-wrap items-center gap-2 text-xs text-zinc-400">
                <a href="{{ route('maintenance.executions.index') }}" class="hover:text-royal-600" wire:navigate>Ejecuciones</a>
                <span>/</span>
                <span>{{ $execution->targetLabel }}</span>
            </div>
            <h1 class="page-title">{{ $execution->targetLabel }}</h1>
            <p class="page-subtitle">
                {{ $execution->type->label() }} · {{ $execution->targetType->label() }}
                @if($execution->planTitle)
                    · Plan {{ $execution->planTitle }}
                @endif
                @if($execution->supportTicketFolio)
                    · Ticket {{ $execution->supportTicketFolio }}
                @endif
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            @if($isEditable)
                <flux:button :href="route('maintenance.executions.edit', $execution->id)" variant="subtle" icon="pencil-square" wire:navigate>
                    Editar
                </flux:button>

                @if($execution->status->value === 'scheduled')
                    <flux:button variant="subtle" icon="play" wire:click="start">
                        Iniciar
                    </flux:button>
                @endif

                <flux:button variant="primary" icon="check-circle" wire:click="complete">
                    Completar
                </flux:button>

                <flux:button variant="danger" icon="x-circle" wire:click="$toggle('showCancel')">
                    Cancelar
                </flux:button>
            @endif
        </div>
    </div>

    @if($showCancel && $isEditable)
        <form wire:submit="cancel" class="rounded-2xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-500/30 dark:bg-rose-500/10">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-rose-800 dark:text-rose-200">Cancelar mantenimiento</h2>
                    <p class="mt-1 text-xs text-rose-700 dark:text-rose-300">Indica el motivo. Esta acción cerrará la ejecución.</p>
                </div>
                <flux:button type="button" size="sm" variant="subtle" icon="x-mark" wire:click="$set('showCancel', false)" />
            </div>
            <div class="mt-4">
                <flux:textarea wire:model="cancellationReason" label="Motivo de cancelación" rows="2" required />
                @error('cancellationReason')
                    <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                @enderror
            </div>
            <div class="mt-4 flex justify-end">
                <flux:button type="submit" variant="danger" icon="x-circle">Confirmar cancelación</flux:button>
            </div>
        </form>
    @endif

    @if($execution->status->value === 'cancelled')
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
            <p class="font-medium">Mantenimiento cancelado</p>
            <p class="mt-1">{{ $execution->cancellationReason ?: 'Sin motivo registrado.' }}</p>
        </div>
    @endif

    @error('checklist')
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
            {{ $message }}
        </div>
    @enderror

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="surface-card p-4">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Estado</p>
            <div class="mt-2">
                <flux:badge :color="$execution->status->color()" size="sm">{{ $execution->status->label() }}</flux:badge>
            </div>
        </div>
        <div class="surface-card p-4">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Programado</p>
            <p class="mt-2 text-sm font-semibold text-navy-900 dark:text-zinc-100">{{ $execution->scheduledAt->format('d/m/Y H:i') }}</p>
        </div>
        <div class="surface-card p-4">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Técnico</p>
            <p class="mt-2 text-sm font-semibold text-navy-900 dark:text-zinc-100">{{ $execution->technicianName ?: 'Sin técnico' }}</p>
        </div>
        <div class="surface-card p-4">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Tiempo invertido</p>
            <p class="mt-2 text-sm font-semibold text-navy-900 dark:text-zinc-100">
                {{ $execution->timeSpentMinutes !== null ? $execution->timeSpentMinutes.' min' : 'Sin registrar' }}
            </p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.05fr_.95fr]">
        <div class="space-y-6">
            <section class="surface-card p-5 sm:p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-semibold text-navy-900 dark:text-zinc-100">Checklist</h2>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ count(array_filter($execution->checklistItems, fn ($item) => $item->isCompleted)) }} de {{ count($execution->checklistItems) }} completados
                        </p>
                    </div>
                    @php
                        $total = count($execution->checklistItems);
                        $done = count(array_filter($execution->checklistItems, fn ($item) => $item->isCompleted));
                        $progress = $total > 0 ? (int) round(($done / $total) * 100) : 0;
                    @endphp
                    <span class="text-sm font-semibold text-navy-900 dark:text-zinc-100">{{ $progress }}%</span>
                </div>

                <div class="mt-3 h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                    <div class="h-full rounded-full bg-royal-600 transition-all" style="width: {{ $progress }}%"></div>
                </div>

                <div class="mt-4 divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse($execution->checklistItems as $item)
                        <div wire:key="maintenance-checklist-{{ $item->id }}" class="py-4">
                            <div class="flex items-start gap-3">
                                <input
                                    type="checkbox"
                                    @checked($item->isCompleted)
                                    @disabled(! $isEditable)
                                    wire:click="toggleChecklist({{ $item->id }})"
                                    class="mt-0.5 size-4 rounded border-zinc-300 text-royal-600 focus:ring-royal-500 disabled:opacity-60 dark:border-zinc-600 dark:bg-zinc-900"
                                    aria-label="Marcar {{ $item->label }}"
                                >
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-sm font-medium {{ $item->isCompleted ? 'text-zinc-400 line-through dark:text-zinc-500' : 'text-navy-900 dark:text-zinc-100' }}">
                                            {{ $item->label }}
                                        </p>
                                        @if($item->isRequired)
                                            <flux:badge color="amber" size="sm">Obligatorio</flux:badge>
                                        @endif
                                    </div>

                                    @if($isEditable)
                                        <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-start">
                                            <input
                                                type="text"
                                                wire:model="checklistNotes.{{ $item->id }}"
                                                maxlength="1000"
                                                placeholder="Nota opcional (máx. 1000 caracteres)"
                                                class="min-w-0 flex-1 rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-xs text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200"
                                            >
                                            <flux:button size="sm" variant="subtle" wire:click="saveNotes({{ $item->id }})">
                                                Guardar nota
                                            </flux:button>
                                        </div>
                                        @error("checklistNotes.{$item->id}")
                                            <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                                        @enderror
                                    @elseif($item->notes)
                                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $item->notes }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="py-4 text-sm text-zinc-500 dark:text-zinc-400">La ejecución no tiene checklist.</p>
                    @endforelse
                </div>
            </section>

            <section class="surface-card p-5 sm:p-6">
                <h2 class="font-semibold text-navy-900 dark:text-zinc-100">Resultado del servicio</h2>
                <dl class="mt-4 space-y-4">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500 dark:text-zinc-400">Hallazgos</dt>
                        <dd class="mt-1 whitespace-pre-line text-sm text-zinc-600 dark:text-zinc-300">{{ $execution->findings ?: 'Sin registrar.' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500 dark:text-zinc-400">Acciones realizadas</dt>
                        <dd class="mt-1 whitespace-pre-line text-sm text-zinc-600 dark:text-zinc-300">{{ $execution->actions ?: 'Sin registrar.' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500 dark:text-zinc-400">Recomendaciones</dt>
                        <dd class="mt-1 whitespace-pre-line text-sm text-zinc-600 dark:text-zinc-300">{{ $execution->recommendations ?: 'Sin registrar.' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500 dark:text-zinc-400">Repuestos</dt>
                        <dd class="mt-1 whitespace-pre-line text-sm text-zinc-600 dark:text-zinc-300">{{ $execution->partsUsed ?: 'Sin registrar.' }}</dd>
                    </div>
                </dl>
            </section>
        </div>

        <div class="space-y-6">
            <section class="surface-card p-5 sm:p-6">
                <h2 class="font-semibold text-navy-900 dark:text-zinc-100">Trazabilidad</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-zinc-500 dark:text-zinc-400">Objetivo</dt>
                        <dd class="text-right font-medium text-navy-900 dark:text-zinc-100">{{ $execution->targetLabel }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-zinc-500 dark:text-zinc-400">Plan</dt>
                        <dd class="text-right font-medium text-navy-900 dark:text-zinc-100">{{ $execution->planTitle ?: 'Correctivo manual' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-zinc-500 dark:text-zinc-400">Ticket</dt>
                        <dd class="text-right font-medium text-navy-900 dark:text-zinc-100">{{ $execution->supportTicketFolio ?: 'Sin ticket' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-zinc-500 dark:text-zinc-400">Inicio</dt>
                        <dd class="text-right font-medium text-navy-900 dark:text-zinc-100">{{ $execution->startedAt?->format('d/m/Y H:i') ?? 'Sin iniciar' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-zinc-500 dark:text-zinc-400">Cierre</dt>
                        <dd class="text-right font-medium text-navy-900 dark:text-zinc-100">{{ $execution->completedAt?->format('d/m/Y H:i') ?? 'Sin cerrar' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-zinc-500 dark:text-zinc-400">Empresa</dt>
                        <dd class="text-right font-medium text-navy-900 dark:text-zinc-100">{{ $companyName }}</dd>
                    </div>
                </dl>
            </section>

            @livewire(\App\Maintenance\Presentation\Livewire\MaintenanceExecutionAttachments::class, ['executionId' => $execution->id], key('maintenance-attachments-'.$execution->id))
        </div>
    </div>
</div>
