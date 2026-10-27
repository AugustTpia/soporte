<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="page-title">Departamentos</h1>
            <p class="page-subtitle">
                Áreas internas de {{ $companyName ?: 'la empresa' }} para organizar el alcance del equipo.
            </p>
        </div>

        @can('create', \App\Departments\Infrastructure\Persistence\Eloquent\Models\DepartmentModel::class)
            <flux:button :href="route('departments.create')" variant="primary" icon="plus" wire:navigate>
                Nuevo departamento
            </flux:button>
        @endcan
    </div>

    <div class="surface-card p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="flex-1">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    icon="magnifying-glass"
                    placeholder="Buscar departamento por nombre o descripción..."
                    aria-label="Buscar departamentos"
                />
            </div>

            <flux:select wire:model.live="status" class="sm:w-48" aria-label="Filtrar por estado">
                <option value="">Todos los estados</option>
                <option value="active">Activos</option>
                <option value="inactive">Inactivos</option>
            </flux:select>
        </div>
    </div>

    <div class="surface-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800 text-sm">
                <thead class="bg-zinc-50/80 dark:bg-zinc-800/60 text-left text-[11px] uppercase tracking-[0.12em] text-zinc-500 dark:text-zinc-400">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Departamento</th>
                        <th class="px-5 py-3 font-semibold">Personas</th>
                        <th class="px-5 py-3 font-semibold">Estado</th>
                        <th class="px-5 py-3 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse($departments as $department)
                        <tr class="transition hover:bg-royal-50/40 dark:hover:bg-zinc-800/60">
                            <td class="px-5 py-4">
                                <div class="flex items-start gap-3">
                                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-royal-50 dark:bg-royal-600/20 text-royal-700 dark:text-royal-300">
                                        <flux:icon.building-office class="size-5" />
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-navy-900 dark:text-zinc-100">{{ $department->name }}</p>
                                        <p class="mt-0.5 line-clamp-2 text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $department->description ?: 'Sin descripción' }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-1.5 text-zinc-600 dark:text-zinc-400">
                                    <flux:icon.users class="size-4 text-zinc-400" />
                                    {{ $department->usersCount }} {{ $department->usersCount === 1 ? 'persona' : 'personas' }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <flux:badge :color="$department->isActive ? 'green' : 'zinc'" size="sm">
                                    {{ $department->isActive ? 'Activo' : 'Inactivo' }}
                                </flux:badge>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    @if(auth()->user()->can('departments.manage'))
                                        <flux:button
                                            size="sm"
                                            variant="subtle"
                                            icon="pencil-square"
                                            :href="route('departments.edit', $department->id)"
                                            wire:navigate
                                            aria-label="Editar {{ $department->name }}"
                                        />

                                        <flux:button
                                            size="sm"
                                            variant="subtle"
                                            :icon="$department->isActive ? 'pause' : 'play'"
                                            wire:click="toggleStatus({{ $department->id }})"
                                            wire:confirm="¿Deseas cambiar el estado de {{ $department->name }}?"
                                            aria-label="{{ $department->isActive ? 'Desactivar' : 'Activar' }} {{ $department->name }}"
                                        />
                                    @else
                                        <span class="text-xs text-zinc-400">Solo consulta</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-14 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <span class="flex size-12 items-center justify-center rounded-2xl bg-royal-50 dark:bg-royal-600/20 text-royal-700 dark:text-royal-300">
                                        <flux:icon.building-office class="size-6" />
                                    </span>
                                    <p class="mt-4 font-medium text-navy-900 dark:text-zinc-100">Aún no hay departamentos</p>
                                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Crea áreas para organizar a las personas y el alcance de la operación.</p>
                                    @can('create', \App\Departments\Infrastructure\Persistence\Eloquent\Models\DepartmentModel::class)
                                        <flux:button class="mt-5" variant="primary" :href="route('departments.create')" wire:navigate>
                                            Crear departamento
                                        </flux:button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($departments->hasPages())
            <div class="border-t border-zinc-100 dark:border-zinc-800 px-5 py-4">
                <flux:pagination :paginator="$departments" />
            </div>
        @endif
    </div>
</div>
