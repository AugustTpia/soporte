<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="mb-2 flex items-center gap-2 text-xs text-zinc-400">
                <a href="{{ route('assets.index') }}" class="hover:text-royal-600" wire:navigate>Activos</a>
                <span>/</span>
                <span>{{ $isEditing ? 'Editar' : 'Nuevo' }}</span>
            </div>
            <h1 class="page-title">{{ $isEditing ? 'Editar activo' : 'Nuevo activo' }}</h1>
            <p class="page-subtitle">
                {{ $companyName ? 'Inventario tecnológico de '.$companyName.'.' : 'Registra un equipo tecnológico.' }}
            </p>
        </div>
    </div>

    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 dark:border-rose-500/30 bg-rose-50 dark:bg-rose-500/10 p-4 text-sm text-rose-700 dark:text-rose-300">
            <p class="font-medium">Revisa la información capturada.</p>
            <ul class="mt-2 list-inside list-disc space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($locationOptions === [])
        <div class="rounded-2xl border border-amber-200 dark:border-amber-500/30 bg-amber-50 dark:bg-amber-500/10 p-4 text-sm text-amber-800 dark:text-amber-300">
            No hay ubicaciones registradas. Crea una ubicación antes de dar de alta activos.
            @can('locations.manage')
                <flux:link :href="route('locations.create')" class="ms-1" wire:navigate>Crear ubicación</flux:link>
            @endcan
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">
        <div class="surface-card p-5 sm:p-6">
            <div class="mb-5">
                <h2 class="text-sm font-semibold text-navy-900 dark:text-zinc-100">Identificación</h2>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Datos para localizar el equipo dentro del inventario.</p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <flux:input
                    wire:model="internalCode"
                    label="Código interno"
                    placeholder="Ej. ACT-000123"
                    maxlength="40"
                    required
                    autofocus
                />

                <flux:input
                    wire:model="hostname"
                    label="Hostname"
                    placeholder="Ej. pc-recepcion-01"
                    maxlength="255"
                />

                <div class="sm:col-span-2">
                    <flux:select wire:model="category" label="Categoría" required>
                        @foreach($categoryOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        </div>

        <div class="surface-card p-5 sm:p-6">
            <div class="mb-5">
                <h2 class="text-sm font-semibold text-navy-900 dark:text-zinc-100">Asignación</h2>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    La ubicación es obligatoria. Los cambios de ubicación o responsable conservan el historial.
                </p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <flux:select wire:model="locationId" label="Ubicación" required>
                    <option value="">Selecciona una ubicación</option>
                    @foreach($locationOptions as $location)
                        <option value="{{ $location['id'] }}">
                            {{ $location['name'] }}{{ $location['is_active'] ? '' : ' (inactiva)' }}
                        </option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="assignedUserId" label="Responsable">
                    <option value="">Sin responsable</option>
                    @foreach($userOptions as $user)
                        <option value="{{ $user['id'] }}">
                            {{ $user['name'] }}{{ $user['is_active'] ? '' : ' (inactivo)' }}
                        </option>
                    @endforeach
                </flux:select>

                @if($isEditing)
                    <div class="sm:col-span-2">
                        <flux:select wire:model="status" label="Estado" required>
                            @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                @endif
            </div>
        </div>

        <div class="surface-card p-5 sm:p-6">
            <div class="mb-5">
                <h2 class="text-sm font-semibold text-navy-900 dark:text-zinc-100">Datos técnicos</h2>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Información opcional para soporte y mantenimiento.</p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <flux:input wire:model="brand" label="Marca" placeholder="Ej. Dell" maxlength="120" />
                <flux:input wire:model="model" label="Modelo" placeholder="Ej. OptiPlex 7010" maxlength="120" />
                <flux:input wire:model="serialNumber" label="Número de serie" placeholder="Ej. ABC123456" maxlength="120" />
                <flux:input wire:model="operatingSystem" label="Sistema operativo" placeholder="Ej. Windows 11 Pro" maxlength="120" />
                <flux:input wire:model="ipAddress" label="Dirección IP" placeholder="Ej. 192.168.1.25" maxlength="45" />
            </div>
        </div>

        <div class="surface-card p-5 sm:p-6">
            <div class="mb-5">
                <h2 class="text-sm font-semibold text-navy-900 dark:text-zinc-100">Compra y garantía</h2>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Fechas de referencia para renovaciones y garantías.</p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <flux:input wire:model="purchaseDate" label="Fecha de compra" type="date" />
                <flux:input wire:model="warrantyUntil" label="Garantía hasta" type="date" />
            </div>
        </div>

        <div class="surface-card p-5 sm:p-6">
            <flux:textarea
                wire:model="notes"
                label="Notas"
                placeholder="Accesorios, condiciones, ubicación física, indicaciones de soporte u otra información útil."
                rows="4"
            />
        </div>

        @if($isEditing && $assignments !== [])
            <div class="surface-card p-5 sm:p-6">
                <div class="mb-5">
                    <h2 class="text-sm font-semibold text-navy-900 dark:text-zinc-100">Historial de asignaciones</h2>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Ubicaciones y responsables anteriores del activo.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800 text-sm">
                        <thead class="text-left text-[11px] uppercase tracking-[0.12em] text-zinc-500 dark:text-zinc-400">
                            <tr>
                                <th class="py-3 pe-4 font-semibold">Ubicación</th>
                                <th class="py-3 pe-4 font-semibold">Responsable</th>
                                <th class="py-3 pe-4 font-semibold">Desde</th>
                                <th class="py-3 font-semibold">Hasta</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach($assignments as $assignment)
                                <tr wire:key="assignment-{{ $assignment->id }}">
                                    <td class="py-3 pe-4 text-zinc-700 dark:text-zinc-300">{{ $assignment->locationName ?: '—' }}</td>
                                    <td class="py-3 pe-4 text-zinc-600 dark:text-zinc-400">{{ $assignment->assignedUserName ?: 'Sin responsable' }}</td>
                                    <td class="py-3 pe-4 text-zinc-600 dark:text-zinc-400">{{ $assignment->assignedAt->format('d/m/Y H:i') }}</td>
                                    <td class="py-3 text-zinc-600 dark:text-zinc-400">
                                        {{ $assignment->releasedAt?->format('d/m/Y H:i') ?? 'Vigente' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
            <flux:button :href="route('assets.index')" variant="ghost" wire:navigate>
                Cancelar
            </flux:button>

            <flux:button variant="primary" type="submit" icon="check">
                {{ $isEditing ? 'Guardar cambios' : 'Crear activo' }}
            </flux:button>
        </div>
    </form>
</div>
