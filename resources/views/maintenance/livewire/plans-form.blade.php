<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="mb-2 flex items-center gap-2 text-xs text-zinc-400">
                <a href="{{ route('maintenance.index') }}" class="hover:text-royal-600" wire:navigate>Mantenimientos</a>
                <span>/</span>
                <span>{{ $isEditing ? 'Editar' : 'Nuevo' }}</span>
            </div>
            <h1 class="page-title">{{ $isEditing ? 'Editar plan de mantenimiento' : 'Nuevo plan de mantenimiento' }}</h1>
            <p class="page-subtitle">
                {{ $companyName ? 'Plan preventivo de '.$companyName.'.' : 'Define la frecuencia y el checklist del servicio.' }}
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
        <div class="surface-card p-5 sm:p-6">
            <div class="mb-5">
                <h2 class="text-sm font-semibold text-navy-900 dark:text-zinc-100">Objetivo del plan</h2>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    Puedes programar el mantenimiento para un activo, una ubicación o toda la empresa.
                </p>
            </div>

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
                        El plan aplicará a toda la empresa activa.
                    </div>
                @endif
            </div>
        </div>

        <div class="surface-card p-5 sm:p-6">
            <div class="mb-5">
                <h2 class="text-sm font-semibold text-navy-900 dark:text-zinc-100">Datos del plan</h2>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    Describe el servicio y su frecuencia para mantener una agenda clara.
                </p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <flux:input
                        wire:model="title"
                        label="Nombre del plan"
                        placeholder="Ej. Mantenimiento preventivo trimestral"
                        maxlength="160"
                        required
                        autofocus
                    />
                </div>

                <div class="sm:col-span-2">
                    <flux:textarea
                        wire:model="description"
                        label="Descripción"
                        placeholder="Alcance, indicaciones y cualquier detalle útil para el técnico."
                        rows="4"
                    />
                </div>

                <flux:input wire:model="intervalValue" label="Cada" type="number" min="1" max="365" required />
                <flux:select wire:model="intervalUnit" label="Unidad" required>
                    @foreach($intervalUnitOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="nextDueAt" label="Próxima fecha" type="datetime-local" required />
                <flux:select wire:model="assignedUserId" label="Responsable" required>
                    <option value="">Selecciona un responsable</option>
                    @foreach($userOptions as $user)
                        <option value="{{ $user['id'] }}">{{ $user['name'] }}</option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        <div class="surface-card p-5 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-navy-900 dark:text-zinc-100">Checklist del plan</h2>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        Estos puntos se copiarán a cada ejecución. Los obligatorios deben completarse para cerrar.
                    </p>
                </div>
                <flux:button size="sm" variant="subtle" icon="plus" wire:click.prevent="addChecklistItem">
                    Agregar punto
                </flux:button>
            </div>

            <div class="mt-4 space-y-3">
                @foreach($checklist as $index => $item)
                    <div wire:key="plan-checklist-{{ $index }}" class="flex flex-col gap-3 rounded-xl border border-zinc-200 p-3 sm:flex-row sm:items-center dark:border-zinc-700">
                        <div class="min-w-0 flex-1">
                            <flux:input
                                wire:model="checklist.{{ $index }}.label"
                                placeholder="Ej. Revisar nivel de tóner"
                                maxlength="200"
                                aria-label="Punto {{ $index + 1 }} del checklist"
                            />
                        </div>

                        <flux:checkbox wire:model="checklist.{{ $index }}.is_required" label="Obligatorio" />

                        <flux:button
                            size="sm"
                            variant="subtle"
                            icon="trash"
                            wire:click.prevent="removeChecklistItem({{ $index }})"
                            aria-label="Eliminar punto {{ $index + 1 }}"
                        />
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
            <flux:button :href="$isEditing ? route('maintenance.plans.show', $planId) : route('maintenance.index')" variant="ghost" wire:navigate>
                Cancelar
            </flux:button>

            <flux:button variant="primary" type="submit" icon="check">
                {{ $isEditing ? 'Guardar cambios' : 'Crear plan' }}
            </flux:button>
        </div>
    </form>
</div>
