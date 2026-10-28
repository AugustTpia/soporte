<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="mb-2 flex items-center gap-2 text-xs text-zinc-400">
                <a href="{{ route('maintenance.executions.index') }}" class="hover:text-royal-600" wire:navigate>Ejecuciones</a>
                <span>/</span>
                <span>{{ $isEditing ? 'Editar' : 'Programar' }}</span>
            </div>
            <h1 class="page-title">{{ $isEditing ? 'Editar ejecución' : ($isPlanBased ? 'Programar mantenimiento' : 'Nuevo correctivo') }}</h1>
            <p class="page-subtitle">
                {{ $companyName ? 'Mantenimiento de '.$companyName.'.' : 'Registra la programación del servicio.' }}
            </p>
        </div>
    </div>

    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
            <p class="font-medium">Revisa la información capturada.</p>
            <ul class="mt-2 list-inside list-disc space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">
        @if($isPlanBased)
            <div class="surface-card p-5 sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-royal-600 dark:text-royal-300">Plan preventivo</p>
                        <h2 class="mt-2 text-lg font-semibold text-navy-900 dark:text-zinc-100">{{ $planTitle }}</h2>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $planTargetLabel }}</p>
                    </div>
                    <flux:badge color="blue" size="sm">Checklist copiado del plan</flux:badge>
                </div>
            </div>
        @endif

        <div class="surface-card p-5 sm:p-6">
            <div class="mb-5">
                <h2 class="text-sm font-semibold text-navy-900 dark:text-zinc-100">Objetivo</h2>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    Define el equipo, sede o empresa sobre el que se realizará el servicio.
                </p>
            </div>

            @if($isEditing)
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800/60 dark:text-zinc-300">
                    <p class="font-medium">{{ $execution?->targetLabel }}</p>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $execution?->targetType->label() }} · El objetivo no se puede cambiar después de programar.</p>
                </div>
            @elseif($isPlanBased)
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800/60 dark:text-zinc-300">
                    {{ $planTargetLabel }}
                </div>
            @else
                <div class="grid gap-5 sm:grid-cols-2">
                    <flux:select wire:model.live="targetType" label="Tipo de objetivo" required>
                        @foreach($targetTypeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>

                    @if($targetType === 'asset')
                        <flux:select wire:model="assetId" label="Activo" required>
                            <option value="">Selecciona un activo</option>
                            @foreach($assetOptions as $asset)
                                <option value="{{ $asset['id'] }}">{{ $asset['label'] }}</option>
                            @endforeach
                        </flux:select>
                    @elseif($targetType === 'location')
                        <flux:select wire:model="locationId" label="Ubicación" required>
                            <option value="">Selecciona una ubicación</option>
                            @foreach($locationOptions as $location)
                                <option value="{{ $location['id'] }}">{{ $location['label'] }}</option>
                            @endforeach
                        </flux:select>
                    @else
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800/60 dark:text-zinc-300">
                            El servicio aplicará a toda la empresa activa.
                        </div>
                    @endif
                </div>
            @endif

            <div class="mt-5">
                <flux:select wire:model="supportTicketId" label="Ticket de soporte (opcional)" :disabled="$isEditing">
                    <option value="">Sin ticket vinculado</option>
                    @foreach($ticketOptions as $ticket)
                        <option value="{{ $ticket['id'] }}">{{ $ticket['label'] }}</option>
                    @endforeach
                </flux:select>
                @if($isEditing)
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">El ticket vinculado no se puede cambiar desde esta edición.</p>
                @endif
            </div>
        </div>

        <div class="surface-card p-5 sm:p-6">
            <div class="mb-5">
                <h2 class="text-sm font-semibold text-navy-900 dark:text-zinc-100">Programación</h2>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    Asigna la fecha y el técnico responsable de la ejecución.
                </p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <flux:input wire:model="scheduledAt" label="Fecha programada" type="datetime-local" required />
                <flux:select wire:model="technicianId" label="Técnico" required>
                    <option value="">Selecciona un técnico</option>
                    @foreach($technicianOptions as $technician)
                        <option value="{{ $technician['id'] }}">{{ $technician['name'] }}</option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        @if($isEditing)
            <div class="surface-card p-5 sm:p-6">
                <div class="mb-5">
                    <h2 class="text-sm font-semibold text-navy-900 dark:text-zinc-100">Resultado del servicio</h2>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        Registra hallazgos y acciones antes de cerrar la ejecución.
                    </p>
                </div>

                <div class="grid gap-5">
                    <flux:textarea wire:model="findings" label="Hallazgos" rows="3" />
                    <flux:textarea wire:model="actions" label="Acciones realizadas" rows="3" />
                    <flux:textarea wire:model="recommendations" label="Recomendaciones" rows="3" />
                    <flux:input wire:model="partsUsed" label="Repuestos utilizados" maxlength="2000" />

                    <div class="grid gap-5 sm:grid-cols-2">
                        <flux:input wire:model="timeSpentMinutes" label="Tiempo invertido (minutos)" type="number" min="0" max="10080" />
                    </div>
                </div>
            </div>
        @endif

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
            <flux:button :href="$isEditing ? route('maintenance.executions.show', $executionId) : route('maintenance.executions.index')" variant="ghost" wire:navigate>
                Cancelar
            </flux:button>

            <flux:button variant="primary" type="submit" icon="check">
                {{ $isEditing ? 'Guardar cambios' : 'Programar mantenimiento' }}
            </flux:button>
        </div>
    </form>
</div>
