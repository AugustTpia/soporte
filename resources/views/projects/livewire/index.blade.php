<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h1 class="page-title">Proyectos</h1>
            <p class="page-subtitle">
                Proyectos de {{ $companyName ?: 'la empresa activa' }}.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            @can('create', \App\Projects\Infrastructure\Persistence\Eloquent\Models\ProjectModel::class)
                <flux:button :href="route('projects.create')" variant="primary" icon="plus" wire:navigate>
                    Nuevo proyecto
                </flux:button>
            @endcan
        </div>
    </div>

    <div class="surface-card p-4">
        <div class="grid gap-3 lg:grid-cols-[1fr_180px_auto]">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Buscar por nombre o descripción..."
                aria-label="Buscar proyectos"
            />

            <flux:select wire:model.live="status" aria-label="Filtrar por estado">
                <option value="">Todos los estados</option>
                @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </flux:select>

            <div class="flex items-center">
                @if($search !== '' || $status !== '')
                    <flux:button size="sm" variant="subtle" icon="x-mark" wire:click="clearFilters">
                        Limpiar filtros
                    </flux:button>
                @endif
            </div>
        </div>
    </div>

    <div class="surface-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                <thead class="bg-zinc-50/80 text-left text-[11px] uppercase tracking-[0.12em] text-zinc-500 dark:bg-zinc-800/60 dark:text-zinc-400">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Proyecto</th>
                        <th class="px-5 py-3 font-semibold">Estado</th>
                        <th class="px-5 py-3 font-semibold">Responsable</th>
                        <th class="px-5 py-3 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse($projects as $project)
                        <?php
                            $canEdit = $canManageAll || auth()->id() === $project->managerId;
                        ?>
                        <tr wire:key="project-{{ $project->id }}" class="transition hover:bg-royal-50/40 dark:hover:bg-zinc-800/60">
                            <td class="px-5 py-4">
                                <div class="flex items-start gap-3">
                                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-royal-50 text-royal-700 dark:bg-royal-600/20 dark:text-royal-300">
                                        <flux:icon.layout-grid class="size-5" />
                                    </span>
                                    <div class="min-w-0">
                                        <a
                                            href="{{ route('projects.show', $project->id) }}"
                                            class="font-semibold text-navy-900 hover:text-royal-700 dark:text-zinc-100 dark:hover:text-royal-300"
                                            wire:navigate
                                        >
                                            {{ $project->name }}
                                        </a>
                                        <p class="mt-0.5 max-w-md truncate text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $project->description ?? 'Sin descripción.' }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <flux:badge :color="$project->status->color()" size="sm">{{ $project->status->label() }}</flux:badge>
                            </td>
                            <td class="px-5 py-4 text-zinc-600 dark:text-zinc-400">{{ $project->managerName ?: 'Sin responsable' }}</td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    <flux:button
                                        size="sm"
                                        variant="subtle"
                                        icon="eye"
                                        :href="route('projects.show', $project->id)"
                                        wire:navigate
                                        aria-label="Ver {{ $project->name }}"
                                    />
                                    @if($canEdit)
                                        <flux:button
                                            size="sm"
                                            variant="subtle"
                                            icon="pencil-square"
                                            :href="route('projects.edit', $project->id)"
                                            wire:navigate
                                            aria-label="Editar {{ $project->name }}"
                                        />
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-14 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <span class="flex size-12 items-center justify-center rounded-2xl bg-royal-50 text-royal-700 dark:bg-royal-600/20 dark:text-royal-300">
                                        <flux:icon.layout-grid class="size-6" />
                                    </span>
                                    <p class="mt-4 font-medium text-navy-900 dark:text-zinc-100">Aún no hay proyectos</p>
                                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                        Crea un proyecto para organizar tareas y equipos.
                                    </p>
                                    @can('create', \App\Projects\Infrastructure\Persistence\Eloquent\Models\ProjectModel::class)
                                        <flux:button class="mt-5" variant="primary" :href="route('projects.create')" wire:navigate>
                                            Crear proyecto
                                        </flux:button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($projects->hasPages())
            <div class="border-t border-zinc-100 px-5 py-4 dark:border-zinc-800">
                <flux:pagination :paginator="$projects" />
            </div>
        @endif
    </div>
</div>
