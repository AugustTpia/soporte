<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
            <div class="mb-2 flex items-center gap-2 text-xs text-zinc-400">
                <a href="{{ route('tickets.index') }}" class="hover:text-royal-600" wire:navigate>Tickets</a>
                <span>/</span>
                <span>{{ $ticket->folio }}</span>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <h1 class="page-title">{{ $ticket->subject }}</h1>
                <flux:badge :color="$ticket->status->color()" size="sm">{{ $ticket->status->label() }}</flux:badge>
                <flux:badge :color="$ticket->priority->color()" size="sm">{{ $ticket->priority->label() }}</flux:badge>
            </div>

            <p class="page-subtitle">
                {{ $ticket->folio }} · {{ $ticket->category->label() }} · Abierto {{ $ticket->openedAt->format('d/m/Y H:i') }}
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            @if($canManage)
                <flux:button :href="route('tickets.edit', $ticket->id)" variant="subtle" icon="pencil-square" wire:navigate>
                    Editar datos
                </flux:button>
            @endif
            <flux:button :href="route('tickets.index')" variant="ghost" wire:navigate>
                Volver
            </flux:button>
        </div>
    </div>

    @if($ticket->status === \App\Support\Domain\Enums\TicketStatus::Cancelled)
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
            <p class="font-medium">Ticket cancelado</p>
            <p class="mt-1">{{ $ticket->cancellationReason ?: 'Sin motivo registrado.' }}</p>
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.55fr)_minmax(340px,0.85fr)]">
        <div class="space-y-6">
            <section class="surface-card p-5 sm:p-6">
                <h2 class="text-sm font-semibold text-navy-900 dark:text-zinc-100">Descripción</h2>
                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-zinc-700 dark:text-zinc-300">{{ $ticket->description }}</p>
            </section>

            <section class="surface-card overflow-hidden">
                <div class="border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
                    <h2 class="font-semibold text-navy-900 dark:text-zinc-100">Historial</h2>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Comentarios, asignaciones y cambios de estado.</p>
                </div>

                @if($canManage)
                    <form wire:submit="addComment" class="border-b border-zinc-100 p-5 dark:border-zinc-800">
                        <flux:textarea
                            wire:model="comment"
                            label="Agregar comentario"
                            placeholder="Registra avances, hallazgos o indicaciones para el equipo."
                            rows="3"
                        />

                        @error('comment')
                            <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
                        @enderror

                        <div class="mt-3 flex justify-end">
                            <flux:button type="submit" size="sm" variant="primary" icon="chat-bubble-left-right">
                                Agregar comentario
                            </flux:button>
                        </div>
                    </form>
                @endif

                <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse($ticket->activities as $activity)
                        <div wire:key="activity-{{ $activity->id }}" class="flex gap-3 px-5 py-4">
                            <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg bg-royal-50 text-royal-700 dark:bg-royal-600/20 dark:text-royal-300">
                                <flux:icon.clock class="size-4" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <p class="text-sm font-medium text-navy-900 dark:text-zinc-100">{{ $activity->type->label() }}</p>
                                    <span class="text-xs text-zinc-400">{{ $activity->actorName ?: 'Sistema' }}</span>
                                    <span class="text-xs text-zinc-400">· {{ $activity->createdAt->format('d/m/Y H:i') }}</span>
                                </div>
                                @if($activity->body)
                                    <p class="mt-1 whitespace-pre-line text-sm leading-6 text-zinc-600 dark:text-zinc-400">{{ $activity->body }}</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            Aún no hay actividad registrada.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="space-y-6">
            <section class="surface-card p-5">
                <h2 class="font-semibold text-navy-900 dark:text-zinc-100">Datos del ticket</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-zinc-500 dark:text-zinc-400">Solicitante</dt>
                        <dd class="text-right font-medium text-navy-900 dark:text-zinc-100">{{ $ticket->requesterName ?: '—' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-zinc-500 dark:text-zinc-400">Contacto</dt>
                        <dd class="text-right text-zinc-700 dark:text-zinc-300">{{ $ticket->requesterContact ?: '—' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-zinc-500 dark:text-zinc-400">Contacto registrado</dt>
                        <dd class="text-right text-zinc-700 dark:text-zinc-300">{{ $ticket->clientContactName ?: '—' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-zinc-500 dark:text-zinc-400">Ubicación</dt>
                        <dd class="text-right text-zinc-700 dark:text-zinc-300">{{ $ticket->locationName ?: '—' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-zinc-500 dark:text-zinc-400">Activo</dt>
                        <dd class="text-right text-zinc-700 dark:text-zinc-300">{{ $ticket->assetLabel ?: '—' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-zinc-500 dark:text-zinc-400">Cuenta</dt>
                        <dd class="text-right text-zinc-700 dark:text-zinc-300">{{ $ticket->accountLabel ?: '—' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-zinc-500 dark:text-zinc-400">Fecha compromiso</dt>
                        <dd class="text-right text-zinc-700 dark:text-zinc-300">{{ $ticket->dueAt?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-zinc-500 dark:text-zinc-400">Resolución</dt>
                        <dd class="text-right text-zinc-700 dark:text-zinc-300">{{ $ticket->resolvedAt?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-zinc-500 dark:text-zinc-400">Cierre</dt>
                        <dd class="text-right text-zinc-700 dark:text-zinc-300">{{ $ticket->closedAt?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-zinc-500 dark:text-zinc-400">Creado por</dt>
                        <dd class="text-right text-zinc-700 dark:text-zinc-300">{{ $ticket->creatorName ?: '—' }}</dd>
                    </div>
                </dl>
            </section>

            @livewire(\App\Support\Presentation\Livewire\Tickets\TicketAttachments::class, [
                'ticketId' => $ticket->id,
            ], key('ticket-attachments-'.$ticket->id))

            <section class="surface-card p-5">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="font-semibold text-navy-900 dark:text-zinc-100">Técnicos asignados</h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Pueden colaborar varios técnicos en el mismo ticket.</p>
                    </div>
                    <flux:badge color="zinc" size="sm">{{ $ticket->activeAssignmentCount() }}</flux:badge>
                </div>

                <div class="mt-4 space-y-2">
                    @forelse($ticket->activeAssignments() as $assignment)
                        <div wire:key="active-assignment-{{ $assignment->id }}" class="flex items-center justify-between gap-3 rounded-xl border border-zinc-200 px-3 py-2 dark:border-zinc-800">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-navy-900 dark:text-zinc-100">{{ $assignment->userName ?: 'Técnico' }}</p>
                                <p class="text-xs text-zinc-400">Desde {{ $assignment->assignedAt->format('d/m/Y H:i') }}</p>
                            </div>
                            @if($canChangeAssignments)
                                <flux:button
                                    size="sm"
                                    variant="subtle"
                                    icon="user-minus"
                                    wire:click="unassign({{ $assignment->userId }})"
                                    wire:confirm="¿Retirar a este técnico del ticket?"
                                    aria-label="Retirar a {{ $assignment->userName }}"
                                />
                            @endif
                        </div>
                    @empty
                        <p class="rounded-xl border border-dashed border-zinc-200 px-3 py-5 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                            Sin técnicos asignados.
                        </p>
                    @endforelse
                </div>

                @if($canChangeAssignments && $ticket->activeAssignments() !== [])
                    <div class="mt-4">
                        <flux:input wire:model="unassignReason" label="Motivo de retiro (opcional)" placeholder="Ej. reasignación por especialidad" maxlength="255" />
                    </div>
                @endif

                @if($canChangeAssignments)
                    <form wire:submit="assign" class="mt-4 space-y-3 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                        <flux:select wire:model="assignUserId" label="Asignar técnico">
                            <option value="">Selecciona un técnico</option>
                            @foreach($assigneeOptions as $option)
                                <option value="{{ $option['id'] }}">{{ $option['name'] }}</option>
                            @endforeach
                        </flux:select>

                        <flux:input wire:model="assignmentReason" label="Motivo de asignación (opcional)" placeholder="Ej. atención presencial" maxlength="255" />

                        @error('assignment')
                            <p class="text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
                        @enderror
                        @error('assignUserId')
                            <p class="text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
                        @enderror

                        <flux:button type="submit" size="sm" variant="primary" icon="user-plus" class="w-full">
                            Asignar técnico
                        </flux:button>
                    </form>
                @endif
            </section>

            <section class="surface-card p-5">
                <h2 class="font-semibold text-navy-900 dark:text-zinc-100">Estado y acciones</h2>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    El motivo es obligatorio al cancelar o reabrir un ticket.
                </p>

                @if($allowedTransitions !== [])
                    <div class="mt-4">
                        <flux:textarea wire:model="statusReason" label="Motivo del cambio (opcional)" rows="2" maxlength="2000" />
                    </div>

                    @error('status')
                        <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror

                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach($allowedTransitions as $status)
                            @if($status === \App\Support\Domain\Enums\TicketStatus::Cancelled)
                                <flux:button
                                    size="sm"
                                    variant="danger"
                                    wire:click="changeStatus('{{ $status->value }}')"
                                    wire:confirm="¿Cancelar este ticket?"
                                >
                                    Cancelar
                                </flux:button>
                            @else
                                <flux:button
                                    size="sm"
                                    variant="subtle"
                                    wire:click="changeStatus('{{ $status->value }}')"
                                >
                                    {{ $status->label() }}
                                </flux:button>
                            @endif
                        @endforeach
                    </div>
                @else
                    <p class="mt-4 rounded-xl border border-dashed border-zinc-200 px-3 py-5 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                        No tienes acciones disponibles para este estado.
                    </p>
                @endif
            </section>
        </div>
    </div>
</div>
