<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="page-title">Tickets</h1>
            <p class="page-subtitle">
                Solicitudes de soporte de {{ $companyName ?: 'la empresa' }}.
            </p>
        </div>

        @can('create', \App\Support\Infrastructure\Persistence\Eloquent\Models\SupportTicketModel::class)
            <flux:button :href="route('tickets.create')" variant="primary" icon="plus" wire:navigate>
                Nuevo ticket
            </flux:button>
        @endcan
    </div>

    <div class="surface-card p-4">
        <div class="grid gap-3 lg:grid-cols-[1fr_180px_180px_180px_210px]">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Buscar por folio, asunto, descripción o solicitante..."
                aria-label="Buscar tickets"
            />

            <flux:select wire:model.live="scope" aria-label="Filtrar por alcance">
                @foreach($scopeOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="status" aria-label="Filtrar por estado">
                <option value="">Todos los estados</option>
                @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="priority" aria-label="Filtrar por prioridad">
                <option value="">Todas las prioridades</option>
                @foreach($priorityOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="category" aria-label="Filtrar por categoría">
                <option value="">Todas las categorías</option>
                @foreach($categoryOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </flux:select>
        </div>

        @if($search !== '' || $scope !== '' || $status !== '' || $priority !== '' || $category !== '')
            <div class="mt-3 flex justify-end">
                <flux:button size="sm" variant="subtle" icon="x-mark" wire:click="clearFilters">
                    Limpiar filtros
                </flux:button>
            </div>
        @endif
    </div>

    <div class="surface-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800 text-sm">
                <thead class="bg-zinc-50/80 dark:bg-zinc-800/60 text-left text-[11px] uppercase tracking-[0.12em] text-zinc-500 dark:text-zinc-400">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Ticket</th>
                        <th class="px-5 py-3 font-semibold">Categoría</th>
                        <th class="px-5 py-3 font-semibold">Técnicos</th>
                        <th class="px-5 py-3 font-semibold">Prioridad</th>
                        <th class="px-5 py-3 font-semibold">Estado</th>
                        <th class="px-5 py-3 font-semibold">Apertura</th>
                        <th class="px-5 py-3 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse($tickets as $ticket)
                        <tr wire:key="ticket-{{ $ticket->id }}" class="transition hover:bg-royal-50/40 dark:hover:bg-zinc-800/60">
                            <td class="px-5 py-4">
                                <div class="flex items-start gap-3">
                                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-royal-50 dark:bg-royal-600/20 text-royal-700 dark:text-royal-300">
                                        <flux:icon.ticket class="size-5" />
                                    </span>
                                    <div class="min-w-0">
                                        <a
                                            href="{{ route('tickets.show', $ticket->id) }}"
                                            class="font-semibold text-navy-900 hover:text-royal-700 dark:text-zinc-100 dark:hover:text-royal-300"
                                            wire:navigate
                                        >
                                            {{ $ticket->folio }}
                                        </a>
                                        <p class="mt-0.5 max-w-md truncate text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $ticket->subject }}
                                        </p>
                                        @if($ticket->assetLabel)
                                            <p class="mt-0.5 text-[11px] text-zinc-400">{{ $ticket->assetLabel }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-zinc-600 dark:text-zinc-400">{{ $ticket->category->label() }}</td>
                            <td class="px-5 py-4">
                                @php($assignments = $ticket->activeAssignments())
                                @if($assignments === [])
                                    <span class="text-xs text-zinc-400">Sin asignar</span>
                                @else
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($assignments as $assignment)
                                            <flux:badge wire:key="assignee-{{ $ticket->id }}-{{ $assignment->userId }}" color="zinc" size="sm">
                                                {{ $assignment->userName ?: 'Técnico' }}
                                            </flux:badge>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <flux:badge :color="$ticket->priority->color()" size="sm">
                                    {{ $ticket->priority->label() }}
                                </flux:badge>
                            </td>
                            <td class="px-5 py-4">
                                <flux:badge :color="$ticket->status->color()" size="sm">
                                    {{ $ticket->status->label() }}
                                </flux:badge>
                            </td>
                            <td class="px-5 py-4 text-zinc-600 dark:text-zinc-400">
                                {{ $ticket->openedAt->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    <flux:button
                                        size="sm"
                                        variant="subtle"
                                        icon="eye"
                                        :href="route('tickets.show', $ticket->id)"
                                        wire:navigate
                                        aria-label="Ver {{ $ticket->folio }}"
                                    />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-14 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <span class="flex size-12 items-center justify-center rounded-2xl bg-royal-50 dark:bg-royal-600/20 text-royal-700 dark:text-royal-300">
                                        <flux:icon.ticket class="size-6" />
                                    </span>
                                    <p class="mt-4 font-medium text-navy-900 dark:text-zinc-100">Aún no hay tickets</p>
                                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                        Registra solicitudes de soporte para dar seguimiento a la operación.
                                    </p>
                                    @can('create', \App\Support\Infrastructure\Persistence\Eloquent\Models\SupportTicketModel::class)
                                        <flux:button class="mt-5" variant="primary" :href="route('tickets.create')" wire:navigate>
                                            Crear ticket
                                        </flux:button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tickets->hasPages())
            <div class="border-t border-zinc-100 dark:border-zinc-800 px-5 py-4">
                <flux:pagination :paginator="$tickets" />
            </div>
        @endif
    </div>
</div>
