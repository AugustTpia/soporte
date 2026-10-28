<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h1 class="page-title">Mantenimientos</h1>
            <p class="page-subtitle">
                Planes preventivos de {{ $companyName ?: 'la empresa' }} y su programación.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <flux:button :href="route('maintenance.executions.index')" variant="subtle" icon="wrench-screwdriver" wire:navigate>
                Ejecuciones
            </flux:button>

            @can('create', \App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenancePlanModel::class)
                <flux:button :href="route('maintenance.plans.create')" variant="primary" icon="plus" wire:navigate>
                    Nuevo plan
                </flux:button>
            @endcan
        </div>
    </div>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="surface-card p-5">
            <div class="flex items-start justify-between">
                <span class="flex size-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                    <flux:icon.check-circle class="size-5" />
                </span>
            </div>
            <p class="mt-4 text-3xl font-semibold tracking-tight text-navy-900 dark:text-zinc-100">{{ $counts['active'] }}</p>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Planes activos</p>
        </div>

        <div class="surface-card p-5">
            <div class="flex items-start justify-between">
                <span class="flex size-10 items-center justify-center rounded-xl bg-royal-50 text-royal-700 dark:bg-royal-600/20 dark:text-royal-300">
                    <flux:icon.calendar-days class="size-5" />
                </span>
            </div>
            <p class="mt-4 text-3xl font-semibold tracking-tight text-navy-900 dark:text-zinc-100">{{ $counts['upcoming'] }}</p>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Próximos 30 días</p>
        </div>

        <div class="surface-card p-5">
            <div class="flex items-start justify-between">
                <span class="flex size-10 items-center justify-center rounded-xl bg-rose-50 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300">
                    <flux:icon.exclamation-triangle class="size-5" />
                </span>
            </div>
            <p class="mt-4 text-3xl font-semibold tracking-tight text-navy-900 dark:text-zinc-100">{{ $counts['overdue'] }}</p>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Vencidos</p>
        </div>

        <div class="surface-card p-5">
            <div class="flex items-start justify-between">
                <span class="flex size-10 items-center justify-center rounded-xl bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                    <flux:icon.pause-circle class="size-5" />
                </span>
            </div>
            <p class="mt-4 text-3xl font-semibold tracking-tight text-navy-900 dark:text-zinc-100">{{ $counts['paused'] }}</p>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Pausados</p>
        </div>
    </section>

    <div class="surface-card p-4">
        <div class="grid gap-3 lg:grid-cols-[1fr_180px_180px_auto]">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Buscar por plan, objetivo o descripción..."
                aria-label="Buscar planes de mantenimiento"
            />

            <flux:select wire:model.live="targetType" aria-label="Filtrar por objetivo">
                <option value="">Todos los objetivos</option>
                @foreach($targetTypeOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="status" aria-label="Filtrar por estado">
                <option value="">Todos los estados</option>
                @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </flux:select>

            <div class="flex items-center">
                <flux:checkbox wire:model.live="overdue" label="Solo vencidos" />
            </div>
        </div>

        @if($search !== '' || $status !== '' || $targetType !== '' || $overdue)
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
                        <th class="px-5 py-3 font-semibold">Plan</th>
                        <th class="px-5 py-3 font-semibold">Objetivo</th>
                        <th class="px-5 py-3 font-semibold">Frecuencia</th>
                        <th class="px-5 py-3 font-semibold">Próximo</th>
                        <th class="px-5 py-3 font-semibold">Responsable</th>
                        <th class="px-5 py-3 font-semibold">Estado</th>
                        <th class="px-5 py-3 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse($plans as $plan)
                        <tr wire:key="maintenance-plan-{{ $plan->id }}" class="transition hover:bg-royal-50/40 dark:hover:bg-zinc-800/60">
                            <td class="px-5 py-4">
                                <div class="flex items-start gap-3">
                                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-royal-50 text-royal-700 dark:bg-royal-600/20 dark:text-royal-300">
                                        <flux:icon.wrench-screwdriver class="size-5" />
                                    </span>
                                    <div class="min-w-0">
                                        <a
                                            href="{{ route('maintenance.plans.show', $plan->id) }}"
                                            class="font-semibold text-navy-900 hover:text-royal-700 dark:text-zinc-100 dark:hover:text-royal-300"
                                            wire:navigate
                                        >
                                            {{ $plan->title }}
                                        </a>
                                        <p class="mt-0.5 max-w-md truncate text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $plan->targetLabel }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-zinc-600 dark:text-zinc-400">{{ $plan->targetType->label() }}</td>
                            <td class="px-5 py-4 text-zinc-600 dark:text-zinc-400">
                                Cada {{ $plan->intervalValue }} {{ mb_strtolower($plan->intervalUnit->label()) }}
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-col gap-1">
                                    <span class="text-zinc-700 dark:text-zinc-300">{{ $plan->nextDueAt->format('d/m/Y') }}</span>
                                    @if($plan->status->value === 'active' && $plan->isOverdue())
                                        <flux:badge color="red" size="sm">Vencido</flux:badge>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-4 text-zinc-600 dark:text-zinc-400">{{ $plan->assignedUserName ?: 'Sin responsable' }}</td>
                            <td class="px-5 py-4">
                                <flux:badge :color="$plan->status->color()" size="sm">{{ $plan->status->label() }}</flux:badge>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    <flux:button
                                        size="sm"
                                        variant="subtle"
                                        icon="eye"
                                        :href="route('maintenance.plans.show', $plan->id)"
                                        wire:navigate
                                        aria-label="Ver {{ $plan->title }}"
                                    />
                                    @if(auth()->user()->can('maintenance.manage'))
                                        <flux:button
                                            size="sm"
                                            variant="subtle"
                                            icon="pencil-square"
                                            :href="route('maintenance.plans.edit', $plan->id)"
                                            wire:navigate
                                            aria-label="Editar {{ $plan->title }}"
                                        />
                                    @endif
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
                                    <p class="mt-4 font-medium text-navy-900 dark:text-zinc-100">Aún no hay planes de mantenimiento</p>
                                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                        Crea un plan preventivo para programar el siguiente servicio.
                                    </p>
                                    @can('create', \App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenancePlanModel::class)
                                        <flux:button class="mt-5" variant="primary" :href="route('maintenance.plans.create')" wire:navigate>
                                            Crear plan
                                        </flux:button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($plans->hasPages())
            <div class="border-t border-zinc-100 px-5 py-4 dark:border-zinc-800">
                <flux:pagination :paginator="$plans" />
            </div>
        @endif
    </div>
</div>
