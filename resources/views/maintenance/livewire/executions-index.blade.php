<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h1 class="page-title">Ejecuciones de mantenimiento</h1>
            <p class="page-subtitle">
                Servicios programados y trabajos correctivos de {{ $companyName ?: 'la empresa' }}.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <flux:button :href="route('maintenance.index')" variant="subtle" icon="wrench-screwdriver" wire:navigate>
                Planes
            </flux:button>

            @can('create', \App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenanceExecutionModel::class)
                <flux:button :href="route('maintenance.executions.create')" variant="primary" icon="plus" wire:navigate>
                    Nuevo correctivo
                </flux:button>
            @endcan
        </div>
    </div>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="surface-card p-5">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Programadas</p>
            <p class="mt-2 text-3xl font-semibold tracking-tight text-navy-900 dark:text-zinc-100">{{ $counts['scheduled'] }}</p>
        </div>
        <div class="surface-card p-5">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">En progreso</p>
            <p class="mt-2 text-3xl font-semibold tracking-tight text-navy-900 dark:text-zinc-100">{{ $counts['in_progress'] }}</p>
        </div>
        <div class="surface-card p-5">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Completadas este mes</p>
            <p class="mt-2 text-3xl font-semibold tracking-tight text-navy-900 dark:text-zinc-100">{{ $counts['completed_month'] }}</p>
        </div>
        <div class="surface-card p-5">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Canceladas este mes</p>
            <p class="mt-2 text-3xl font-semibold tracking-tight text-navy-900 dark:text-zinc-100">{{ $counts['cancelled_month'] }}</p>
        </div>
    </section>

    <div class="surface-card p-4">
        <div class="grid gap-3 lg:grid-cols-[1fr_180px_180px_180px]">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Buscar por objetivo, plan, hallazgos o ticket..."
                aria-label="Buscar ejecuciones de mantenimiento"
            />

            <flux:select wire:model.live="status" aria-label="Filtrar por estado">
                <option value="">Todos los estados</option>
                @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="type" aria-label="Filtrar por tipo">
                <option value="">Todos los tipos</option>
                @foreach($typeOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </flux:select>

            <div class="flex items-center">
                @if($plan !== '')
                    <flux:badge color="royal" size="sm">Filtrado por plan</flux:badge>
                @endif
            </div>
        </div>

        @if($search !== '' || $status !== '' || $type !== '' || $plan !== '')
            <div class="mt-3 flex justify-end">
                <flux:button size="sm" variant="subtle" icon="x-mark" wire:click="clearFilters">
                    Limpiar filtros
                </flux:button>
            </div>
        @endif
    </div>

    <div class="surface-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                <thead class="bg-zinc-50/80 text-left text-[11px] uppercase tracking-[0.12em] text-zinc-500 dark:bg-zinc-800/60 dark:text-zinc-400">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Objetivo</th>
                        <th class="px-5 py-3 font-semibold">Plan / ticket</th>
                        <th class="px-5 py-3 font-semibold">Tipo</th>
                        <th class="px-5 py-3 font-semibold">Técnico</th>
                        <th class="px-5 py-3 font-semibold">Programada</th>
                        <th class="px-5 py-3 font-semibold">Estado</th>
                        <th class="px-5 py-3 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse($executions as $execution)
                        <tr wire:key="maintenance-execution-{{ $execution->id }}" class="transition hover:bg-royal-50/40 dark:hover:bg-zinc-800/60">
                            <td class="px-5 py-4">
                                <div class="flex items-start gap-3">
                                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-royal-50 text-royal-700 dark:bg-royal-600/20 dark:text-royal-300">
                                        <flux:icon.wrench-screwdriver class="size-5" />
                                    </span>
                                    <div class="min-w-0">
                                        <a
                                            href="{{ route('maintenance.executions.show', $execution->id) }}"
                                            class="font-semibold text-navy-900 hover:text-royal-700 dark:text-zinc-100 dark:hover:text-royal-300"
                                            wire:navigate
                                        >
                                            {{ $execution->targetLabel }}
                                        </a>
                                        <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $execution->targetType->label() }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-zinc-600 dark:text-zinc-400">
                                {{ $execution->planTitle ?: 'Sin plan' }}
                                @if($execution->supportTicketFolio)
                                    <p class="mt-0.5 text-xs text-zinc-400">Ticket {{ $execution->supportTicketFolio }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <flux:badge :color="$execution->type->color()" size="sm">{{ $execution->type->label() }}</flux:badge>
                            </td>
                            <td class="px-5 py-4 text-zinc-600 dark:text-zinc-400">{{ $execution->technicianName ?: 'Sin técnico' }}</td>
                            <td class="px-5 py-4 text-zinc-600 dark:text-zinc-400">{{ $execution->scheduledAt->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-4">
                                <flux:badge :color="$execution->status->color()" size="sm">{{ $execution->status->label() }}</flux:badge>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    <flux:button
                                        size="sm"
                                        variant="subtle"
                                        icon="eye"
                                        :href="route('maintenance.executions.show', $execution->id)"
                                        wire:navigate
                                        aria-label="Ver mantenimiento {{ $execution->targetLabel }}"
                                    />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-14 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <span class="flex size-12 items-center justify-center rounded-2xl bg-royal-50 text-royal-700 dark:bg-royal-600/20 dark:text-royal-300">
                                        <flux:icon.wrench-screwdriver class="size-6" />
                                    </span>
                                    <p class="mt-4 font-medium text-navy-900 dark:text-zinc-100">Aún no hay ejecuciones</p>
                                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                        Programa un plan o registra un trabajo correctivo.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($executions->hasPages())
            <div class="border-t border-zinc-100 px-5 py-4 dark:border-zinc-800">
                <flux:pagination :paginator="$executions" />
            </div>
        @endif
    </div>
</div>
