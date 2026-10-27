<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="page-title">Activos</h1>
            <p class="page-subtitle">
                Equipos y dispositivos tecnológicos de {{ $companyName ?: 'la empresa' }}.
            </p>
        </div>

        @can('create', \App\Assets\Infrastructure\Persistence\Eloquent\Models\AssetModel::class)
            <flux:button :href="route('assets.create')" variant="primary" icon="plus" wire:navigate>
                Nuevo activo
            </flux:button>
        @endcan
    </div>

    <div class="surface-card p-4">
        <div class="grid gap-3 lg:grid-cols-[1fr_190px_190px_210px]">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Buscar por código, hostname, serie, marca o responsable..."
                aria-label="Buscar activos"
            />

            <flux:select wire:model.live="category" aria-label="Filtrar por categoría">
                <option value="">Todas las categorías</option>
                @foreach($categoryOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="status" aria-label="Filtrar por estado">
                <option value="">Todos los estados</option>
                @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="locationId" aria-label="Filtrar por ubicación">
                <option value="">Todas las ubicaciones</option>
                @foreach($locationOptions as $location)
                    <option value="{{ $location['id'] }}">
                        {{ $location['name'] }}{{ $location['is_active'] ? '' : ' (inactiva)' }}
                    </option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <div class="surface-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800 text-sm">
                <thead class="bg-zinc-50/80 dark:bg-zinc-800/60 text-left text-[11px] uppercase tracking-[0.12em] text-zinc-500 dark:text-zinc-400">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Activo</th>
                        <th class="px-5 py-3 font-semibold">Categoría</th>
                        <th class="px-5 py-3 font-semibold">Ubicación</th>
                        <th class="px-5 py-3 font-semibold">Responsable</th>
                        <th class="px-5 py-3 font-semibold">Estado</th>
                        <th class="px-5 py-3 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse($assets as $asset)
                        <tr wire:key="asset-{{ $asset->id }}" class="transition hover:bg-royal-50/40 dark:hover:bg-zinc-800/60">
                            <td class="px-5 py-4">
                                <div class="flex items-start gap-3">
                                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-royal-50 dark:bg-royal-600/20 text-royal-700 dark:text-royal-300">
                                        <flux:icon.computer-desktop class="size-5" />
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-navy-900 dark:text-zinc-100">{{ $asset->internalCode }}</p>
                                        <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $asset->hostname ?: 'Sin hostname' }}
                                            @if($asset->brand || $asset->model)
                                                · {{ trim($asset->brand.' '.$asset->model) }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-zinc-600 dark:text-zinc-400">
                                {{ $asset->category->label() }}
                                @if($asset->serialNumber)
                                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Serie: {{ $asset->serialNumber }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-zinc-600 dark:text-zinc-400">
                                {{ $asset->locationName ?: 'Sin ubicación' }}
                            </td>
                            <td class="px-5 py-4 text-zinc-600 dark:text-zinc-400">
                                {{ $asset->assignedUserName ?: 'Sin responsable' }}
                            </td>
                            <td class="px-5 py-4">
                                <flux:badge :color="$asset->status->color()" size="sm">
                                    {{ $asset->status->label() }}
                                </flux:badge>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    @if(auth()->user()->can('assets.manage'))
                                        <flux:button
                                            size="sm"
                                            variant="subtle"
                                            icon="pencil-square"
                                            :href="route('assets.edit', $asset->id)"
                                            wire:navigate
                                            aria-label="Editar {{ $asset->internalCode }}"
                                        />
                                    @else
                                        <span class="text-xs text-zinc-400">Solo consulta</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-14 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <span class="flex size-12 items-center justify-center rounded-2xl bg-royal-50 dark:bg-royal-600/20 text-royal-700 dark:text-royal-300">
                                        <flux:icon.computer-desktop class="size-6" />
                                    </span>
                                    <p class="mt-4 font-medium text-navy-900 dark:text-zinc-100">Aún no hay activos</p>
                                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                        Registra equipos para ubicarlos, asignarlos y conservar su historial.
                                    </p>
                                    @can('create', \App\Assets\Infrastructure\Persistence\Eloquent\Models\AssetModel::class)
                                        <flux:button class="mt-5" variant="primary" :href="route('assets.create')" wire:navigate>
                                            Crear activo
                                        </flux:button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($assets->hasPages())
            <div class="border-t border-zinc-100 dark:border-zinc-800 px-5 py-4">
                <flux:pagination :paginator="$assets" />
            </div>
        @endif
    </div>
</div>
