<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="page-title">Ubicaciones</h1>
            <p class="page-subtitle">
                Sucursales y direcciones operativas de {{ $companyName ?: 'la empresa' }}.
            </p>
        </div>

        @can('create', \App\Locations\Infrastructure\Persistence\Eloquent\Models\LocationModel::class)
            <flux:button :href="route('locations.create')" variant="primary" icon="plus" wire:navigate>
                Nueva ubicación
            </flux:button>
        @endcan
    </div>

    <div class="surface-card p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="flex-1">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    icon="magnifying-glass"
                    placeholder="Buscar por nombre, calle, colonia o ciudad..."
                    aria-label="Buscar ubicaciones"
                />
            </div>

            <flux:select wire:model.live="status" class="sm:w-48" aria-label="Filtrar por estado">
                <option value="">Todos los estados</option>
                <option value="active">Activas</option>
                <option value="inactive">Inactivas</option>
            </flux:select>
        </div>
    </div>

    <div class="surface-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800 text-sm">
                <thead class="bg-zinc-50/80 dark:bg-zinc-800/60 text-left text-[11px] uppercase tracking-[0.12em] text-zinc-500 dark:text-zinc-400">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Ubicación</th>
                        <th class="px-5 py-3 font-semibold">Dirección</th>
                        <th class="px-5 py-3 font-semibold">Ciudad</th>
                        <th class="px-5 py-3 font-semibold">Estado</th>
                        <th class="px-5 py-3 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse($locations as $location)
                        <tr wire:key="location-{{ $location->id }}" class="transition hover:bg-royal-50/40 dark:hover:bg-zinc-800/60">
                            <td class="px-5 py-4">
                                <div class="flex items-start gap-3">
                                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-royal-50 dark:bg-royal-600/20 text-royal-700 dark:text-royal-300">
                                        <flux:icon.map-pin class="size-5" />
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-navy-900 dark:text-zinc-100">{{ $location->name }}</p>
                                        <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $location->phone ?: 'Sin teléfono' }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ $location->street }} {{ $location->exteriorNumber }}
                                    @if($location->interiorNumber)
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400">Int. {{ $location->interiorNumber }}</span>
                                    @endif
                                </p>
                                @if($location->neighborhood || $location->postalCode)
                                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $location->neighborhood }}
                                        @if($location->neighborhood && $location->postalCode) · @endif
                                        @if($location->postalCode) CP {{ $location->postalCode }} @endif
                                    </p>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-zinc-600 dark:text-zinc-400">
                                {{ $location->city }}
                                @if($location->state)
                                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $location->state }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <flux:badge :color="$location->isActive ? 'green' : 'zinc'" size="sm">
                                    {{ $location->isActive ? 'Activa' : 'Inactiva' }}
                                </flux:badge>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    @if(auth()->user()->can('locations.manage'))
                                        <flux:button
                                            size="sm"
                                            variant="subtle"
                                            icon="pencil-square"
                                            :href="route('locations.edit', $location->id)"
                                            wire:navigate
                                            aria-label="Editar {{ $location->name }}"
                                        />

                                        <flux:button
                                            size="sm"
                                            variant="subtle"
                                            :icon="$location->isActive ? 'pause' : 'play'"
                                            wire:click="toggleStatus({{ $location->id }})"
                                            wire:confirm="¿Deseas cambiar el estado de {{ $location->name }}?"
                                            aria-label="{{ $location->isActive ? 'Desactivar' : 'Activar' }} {{ $location->name }}"
                                        />
                                    @else
                                        <span class="text-xs text-zinc-400">Solo consulta</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-14 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <span class="flex size-12 items-center justify-center rounded-2xl bg-royal-50 dark:bg-royal-600/20 text-royal-700 dark:text-royal-300">
                                        <flux:icon.map-pin class="size-6" />
                                    </span>
                                    <p class="mt-4 font-medium text-navy-900 dark:text-zinc-100">Aún no hay ubicaciones</p>
                                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                        Registra sucursales y direcciones para vincular activos, contactos y soporte.
                                    </p>
                                    @can('create', \App\Locations\Infrastructure\Persistence\Eloquent\Models\LocationModel::class)
                                        <flux:button class="mt-5" variant="primary" :href="route('locations.create')" wire:navigate>
                                            Crear ubicación
                                        </flux:button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($locations->hasPages())
            <div class="border-t border-zinc-100 dark:border-zinc-800 px-5 py-4">
                <flux:pagination :paginator="$locations" />
            </div>
        @endif
    </div>
</div>
