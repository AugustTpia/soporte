<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <div class="mb-2 flex items-center gap-2 text-xs text-zinc-400">
                <a href="{{ route('projects.index') }}" class="hover:text-royal-600" wire:navigate>Proyectos</a>
                <span>/</span>
                <span>{{ $project->name }}</span>
            </div>
            <h1 class="page-title">{{ $project->name }}</h1>
            <p class="page-subtitle">{{ $project->companyName }}</p>
        </div>

        <div class="flex flex-wrap gap-2">
            @if($canManage)
                <flux:button :href="route('projects.edit', $project->projectId)" variant="subtle" icon="pencil-square" wire:navigate>
                    Editar proyecto
                </flux:button>
            @endif

            <flux:button size="sm" variant="primary" icon="plus" wire:click="openTaskModal">
                Nueva tarea
            </flux:button>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="surface-card p-4">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Estado</p>
            <div class="mt-2">
                <flux:badge :color="\App\Projects\Domain\Enums\ProjectStatus::from($project->status)->color()" size="sm">
                    {{ \App\Projects\Domain\Enums\ProjectStatus::from($project->status)->label() }}
                </flux:badge>
            </div>
        </div>

        <div class="surface-card p-4">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Responsable</p>
            <p class="mt-2 text-sm font-semibold text-navy-900 dark:text-zinc-100">{{ $project->managerName ?: 'Sin responsable' }}</p>
        </div>

        <div class="surface-card p-4">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Inicio</p>
            <p class="mt-2 text-sm font-semibold text-navy-900 dark:text-zinc-100">{{ $project->startDate ?: 'Sin fecha' }}</p>
        </div>

        <div class="surface-card p-4">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Vencimiento</p>
            <p class="mt-2 text-sm font-semibold text-navy-900 dark:text-zinc-100">{{ $project->dueDate ?: 'Sin fecha' }}</p>
        </div>
    </div>

    @if($project->description)
        <div class="surface-card p-4">
            <h3 class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500 dark:text-zinc-400">Descripción</h3>
            <p class="mt-2 whitespace-pre-line text-sm text-zinc-600 dark:text-zinc-300">{{ $project->description }}</p>
        </div>
    @endif

    <div class="surface-card p-4">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold text-navy-900 dark:text-zinc-100">Equipo</h2>
            <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ count($project->members) }} miembro(s)</p>
        </div>

        <div class="grid gap-2 sm:grid-cols-3 lg:grid-cols-4">
            @forelse($project->members as $member)
                <div class="flex items-center gap-2 rounded-xl border border-zinc-200 p-2 dark:border-zinc-700">
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                        <flux:icon.user class="size-4" />
                    </span>
                    <p class="text-sm text-zinc-700 dark:text-zinc-300 truncate">{{ $member['name'] }}</p>
                </div>
            @empty
                <p class="text-sm text-zinc-500 dark:text-zinc-400">El proyecto no tiene miembros.</p>
            @endforelse
        </div>
    </div>

    <div class="surface-card p-4">
        <h2 class="text-sm font-semibold text-navy-900 dark:text-zinc-100 mb-4">Tablero de tareas</h2>

        <div class="grid gap-4 sm:grid-cols-4">
            @foreach($boards as $status => $column)
                <div class="flex flex-col gap-2 rounded-xl border border-zinc-200 p-3 dark:border-zinc-700">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-xs font-semibold text-zinc-600 dark:text-zinc-400">{{ $column['label'] }}</h3>
                        <flux:badge size="xs">{{ count($column['tasks']) }}</flux:badge>
                    </div>

                    <div class="space-y-2">
                        @forelse($column['tasks'] as $task)
                            @php
                                $statusEnum = \App\Projects\Domain\Enums\ProjectTaskStatus::from($task['status']);
                                $transitions = $statusEnum->allowedTransitions();
                                $isAssigned = auth()->id() === $task['assigned_user_id'];
                            @endphp
                            <div wire:key="project-task-{{ $task['id'] }}" class="rounded-lg border border-zinc-200 p-3 transition hover:bg-royal-50/40 dark:hover:bg-zinc-800/60 dark:border-zinc-700">
                                <div class="flex items-center justify-between mb-1">
                                    <p class="text-sm font-semibold text-navy-900 dark:text-zinc-100 truncate">{{ $task['title'] }}</p>
                                    <flux:badge :color="\App\Projects\Domain\Enums\ProjectTaskPriority::from($task['priority'])->color()" size="xs">
                                        {{ \App\Projects\Domain\Enums\ProjectTaskPriority::from($task['priority'])->label() }}
                                    </flux:badge>
                                </div>

                                @if($task['description'])
                                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400 truncate">{{ $task['description'] }}</p>
                                @endif

                                <div class="mt-2 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="flex items-center gap-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                                        @if($task['assigned_user_name'])
                                            <span class="flex size-5 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                                <flux:icon.user class="size-3" />
                                            </span>
                                            <span class="truncate">{{ $task['assigned_user_name'] }}</span>
                                        @else
                                            <span class="text-rose-400">Sin responsable</span>
                                        @endif
                                    </div>

                                    @if($task['ticket_id'])
                                        <a
                                            href="{{ route('tickets.show', $task['ticket_id']) }}"
                                            class="flex items-center gap-1.5 text-xs text-royal-600 hover:text-royal-700 dark:text-royal-400 dark:hover:text-royal-300"
                                            wire:navigate
                                        >
                                            <flux:icon.ticket class="size-3" />
                                            <span>{{ $task['ticket_label'] }}</span>
                                        </a>
                                    @endif

                                    @if($task['due_date'])
                                        <span class="flex items-center gap-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                                            <flux:icon.calendar-days class="size-3" />
                                            <span>{{ $task['due_date'] }}</span>
                                        </span>
                                    @endif
                                </div>

                                <div class="mt-3 flex flex-col gap-1 sm:flex-row sm:items-center">
                                    @foreach($transitions as $target)
                                        <flux:button
                                            size="sm"
                                            variant="subtle"
                                            icon="arrow-right"
                                            :color="$target->color()"
                                            wire:click.prevent="moveTask({{ $task['id'] }}, '{{ $target->value }}', {{ $task['sort_order'] }})"
                                            aria-label="Mover a {{ $target->label() }}"
                                        >
                                            {{ $target->label() }}
                                        </flux:button>
                                    @endforeach

                                    @if($canManage || $isAssigned)
                                        <flux:button
                                            size="sm"
                                            variant="subtle"
                                            icon="pencil-square"
                                            wire:click.prevent="openTaskModal({{ $task['id'] }})"
                                            aria-label="Editar {{ $task['title'] }}"
                                        />
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 text-center">Sin tareas en esta columna.</p>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @if($taskModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/25"></div>

            <div class="relative bg-white dark:bg-zinc-900 rounded-2xl p-6 w-full max-w-2xl border border-zinc-200 dark:border-zinc-700 shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-navy-900 dark:text-zinc-100">{{ $taskModalId ? 'Editar tarea' : 'Nueva tarea' }}</h2>
                    <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="$set('taskModalOpen', false)" />
                </div>

                <form class="space-y-4">
                    <div>
                        <flux:input
                            wire:model.live="taskModalTitle"
                            label="Título"
                            placeholder="Ej. Configurar servidor de respaldo"
                            maxlength="200"
                            required
                            autofocus
                        />
                    </div>

                    <div>
                        <flux:textarea
                            wire:model.live="taskModalDescription"
                            label="Descripción"
                            placeholder="Alcance y pasos de la tarea."
                            rows="3"
                        />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <flux:select wire:model="taskModalStatus" label="Estado" required>
                            @foreach(\App\Projects\Domain\Enums\ProjectTaskStatus::cases() as $status)
                                <option value="{{ $status->value }}">{{ $status->label() }}</option>
                            @endforeach
                        </flux:select>

                        <flux:select wire:model="taskModalPriority" label="Prioridad" required>
                            @foreach(\App\Projects\Domain\Enums\ProjectTaskPriority::cases() as $priority)
                                <option value="{{ $priority->value }}">{{ $priority->label() }}</option>
                            @endforeach
                        </flux:select>

                        @if($canManage)
                            <flux:select wire:model="taskModalAssignedUserId" label="Responsable" required>
                                <option value="">Selecciona un responsable</option>
                                @foreach($assignableUsers as $user)
                                    <option value="{{ $user['id'] }}">{{ $user['name'] }}</option>
                                @endforeach
                            </flux:select>

                            <flux:select wire:model="taskModalTicketId" label="Ticket vinculado">
                                <option value="">Sin ticket</option>
                                @foreach($ticketOptions as $ticket)
                                    <option value="{{ $ticket['id'] }}">{{ $ticket['label'] }}</option>
                                @endforeach
                            </flux:select>
                        @else
                            <input type="hidden" wire:model="taskModalAssignedUserId" />
                            <input type="hidden" wire:model="taskModalTicketId" />
                        @endif

                        <flux:input wire:model="taskModalDueDate" label="Fecha de vencimiento" type="date" />
                    </div>

                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                        <flux:button variant="ghost" wire:click="$set('taskModalOpen', false)">
                            Cancelar
                        </flux:button>

                        <flux:button variant="primary" wire:click.prevent="saveTask">
                            Guardar tarea
                        </flux:button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
