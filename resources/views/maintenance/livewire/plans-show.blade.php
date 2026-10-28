<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <div class="mb-2 flex items-center gap-2 text-xs text-zinc-400">
                <a href="{{ route('maintenance.index') }}" class="hover:text-royal-600" wire:navigate>Mantenimientos</a>
                <span>/</span>
                <span>{{ $plan->title }}</span>
            </div>
            <h1 class="page-title">{{ $plan->title }}</h1>
            <p class="page-subtitle">{{ $plan->targetLabel }} · {{ $companyName }}</p>
        </div>

        <div class="flex flex-wrap gap-2">
            @if($canManage && $plan->status->value === 'active')
                <flux:button
                    :href="route('maintenance.executions.create', ['plan' => $plan->id])"
                    variant="primary"
                    icon="calendar-days"
                    wire:navigate
                >
                    Programar ejecución
                </flux:button>
            @endif

            @if($canManage)
                <flux:button :href="route('maintenance.plans.edit', $plan->id)" variant="subtle" icon="pencil-square" wire:navigate>
                    Editar
                </flux:button>
                <flux:button variant="subtle" icon="pause" wire:click="toggleStatus">
                    {{ $plan->status->value === 'active' ? 'Pausar' : 'Reactivar' }}
                </flux:button>
            @endif
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="surface-card p-4">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Estado</p>
            <div class="mt-2">
                <flux:badge :color="$plan->status->color()" size="sm">{{ $plan->status->label() }}</flux:badge>
            </div>
        </div>
        <div class="surface-card p-4">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Próxima ejecución</p>
            <p class="mt-2 text-sm font-semibold text-navy-900 dark:text-zinc-100">{{ $plan->nextDueAt->format('d/m/Y H:i') }}</p>
            @if($plan->status->value === 'active' && $plan->isOverdue())
                <p class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400">Vencido</p>
            @endif
        </div>
        <div class="surface-card p-4">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Frecuencia</p>
            <p class="mt-2 text-sm font-semibold text-navy-900 dark:text-zinc-100">
                Cada {{ $plan->intervalValue }} {{ mb_strtolower($plan->intervalUnit->label()) }}
            </p>
        </div>
        <div class="surface-card p-4">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Responsable</p>
            <p class="mt-2 text-sm font-semibold text-navy-900 dark:text-zinc-100">{{ $plan->assignedUserName ?: 'Sin responsable' }}</p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.15fr_.85fr]">
        <section class="surface-card p-5 sm:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-navy-900 dark:text-zinc-100">Checklist</h2>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        {{ count($plan->checklistItems) }} puntos configurados.
                    </p>
                </div>
            </div>

            <div class="mt-4 divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse($plan->checklistItems as $item)
                    <div class="flex items-start gap-3 py-3">
                        <span class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-royal-50 text-[11px] font-semibold text-royal-700 dark:bg-royal-600/20 dark:text-royal-300">
                            {{ $loop->iteration }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $item->label }}</p>
                            <p class="mt-0.5 text-[11px] text-zinc-400">{{ $item->isRequired ? 'Obligatorio' : 'Opcional' }}</p>
                        </div>
                    </div>
                @empty
                    <p class="py-4 text-sm text-zinc-500 dark:text-zinc-400">El plan no tiene checklist.</p>
                @endforelse
            </div>

            @if($plan->description)
                <div class="mt-5 border-t border-zinc-100 pt-5 dark:border-zinc-800">
                    <h3 class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500 dark:text-zinc-400">Descripción</h3>
                    <p class="mt-2 whitespace-pre-line text-sm text-zinc-600 dark:text-zinc-300">{{ $plan->description }}</p>
                </div>
            @endif
        </section>

        <section class="surface-card p-5 sm:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-navy-900 dark:text-zinc-100">Última ejecución</h2>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Historial reciente del plan.</p>
                </div>
                <flux:button size="sm" variant="subtle" :href="route('maintenance.executions.index', ['plan' => $plan->id])" wire:navigate>
                    Ver todas
                </flux:button>
            </div>

            <div class="mt-4 divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse(array_slice($executions, 0, 5) as $execution)
                    <a
                        href="{{ route('maintenance.executions.show', $execution->id) }}"
                        wire:key="plan-execution-{{ $execution->id }}"
                        class="flex items-center justify-between gap-3 py-3 transition hover:text-royal-700 dark:hover:text-royal-300"
                        wire:navigate
                    >
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-navy-900 dark:text-zinc-100">{{ $execution->scheduledAt->format('d/m/Y H:i') }}</p>
                            <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $execution->technicianName ?: 'Sin técnico' }}</p>
                        </div>
                        <flux:badge :color="$execution->status->color()" size="sm">{{ $execution->status->label() }}</flux:badge>
                    </a>
                @empty
                    <p class="py-4 text-sm text-zinc-500 dark:text-zinc-400">Aún no hay ejecuciones para este plan.</p>
                @endforelse
            </div>

            <div class="mt-5 rounded-xl bg-zinc-50 p-4 text-xs text-zinc-500 dark:bg-zinc-800/60 dark:text-zinc-400">
                Último cierre:
                <span class="font-medium text-zinc-700 dark:text-zinc-200">
                    {{ $plan->lastCompletedAt?->format('d/m/Y H:i') ?? 'Sin cierres' }}
                </span>
            </div>
        </section>
    </div>
</div>
