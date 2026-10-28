<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="page-title">Empresas</h1>
            <p class="page-subtitle">Administra las empresas, su información de contacto y su estado operativo.</p>
        </div>

        <flux:button :href="route('companies.create')" variant="primary" icon="plus" wire:navigate>
            Nueva empresa
        </flux:button>
    </div>

    <div class="surface-card p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="flex-1">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    icon="magnifying-glass"
                    placeholder="Buscar por nombre, RFC, correo..."
                    aria-label="Buscar empresas"
                />
            </div>

            <flux:text class="text-xs text-zinc-400">
                {{ $companies->total() }} {{ $companies->total() === 1 ? 'empresa' : 'empresas' }}
            </flux:text>
        </div>
    </div>

    <div class="surface-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800 text-sm">
                <thead class="bg-zinc-50/80 dark:bg-zinc-800/60 text-left text-[11px] uppercase tracking-[0.12em] text-zinc-500 dark:text-zinc-400">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Empresa</th>
                        <th class="px-5 py-3 font-semibold">Contacto</th>
                        <th class="px-5 py-3 font-semibold">Equipo</th>
                        <th class="px-5 py-3 font-semibold">Estado</th>
                        <th class="px-5 py-3 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse($companies as $company)
                        <tr class="transition hover:bg-royal-50/40 dark:hover:bg-zinc-800/60">
                            <td class="px-5 py-4">
                                <div class="flex items-start gap-3">
                                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-royal-50 dark:bg-royal-600/20 text-royal-700 dark:text-royal-300">
                                        <flux:icon.building-office-2 class="size-5" />
                                    </span>
                                    <div class="min-w-0">
                                        <a href="{{ route('companies.show', $company->publicId) }}" wire:navigate class="truncate font-semibold text-navy-900 hover:text-royal-700 dark:text-zinc-100 dark:hover:text-royal-300">
                                            {{ $company->name }}
                                        </a>
                                        <p class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $company->legalName ?: 'Sin razón social registrada' }}
                                        </p>
                                        @if($company->taxId)
                                            <p class="mt-0.5 text-[11px] text-zinc-400">RFC {{ $company->taxId }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <p class="text-zinc-700 dark:text-zinc-300">{{ $company->email ?: 'Sin correo' }}</p>
                                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $company->phone ?: 'Sin teléfono' }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-4 text-xs text-zinc-600 dark:text-zinc-400">
                                    <span class="inline-flex items-center gap-1.5">
                                        <flux:icon.users class="size-4 text-zinc-400" />
                                        {{ $company->usersCount }} usuarios
                                    </span>
                                    <span class="inline-flex items-center gap-1.5">
                                        <flux:icon.building-office class="size-4 text-zinc-400" />
                                        {{ $company->departmentsCount }} áreas
                                    </span>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <flux:badge :color="$company->isActive() ? 'green' : 'zinc'" size="sm">
                                    {{ $company->isActive() ? 'Activa' : 'Inactiva' }}
                                </flux:badge>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    <flux:button
                                        size="sm"
                                        variant="subtle"
                                        icon="eye"
                                        :href="route('companies.show', $company->publicId)"
                                        wire:navigate
                                        aria-label="Ver ficha de {{ $company->name }}"
                                    />

                                    <flux:button
                                        size="sm"
                                        variant="subtle"
                                        icon="pencil-square"
                                        :href="route('companies.edit', $company->publicId)"
                                        wire:navigate
                                        aria-label="Editar {{ $company->name }}"
                                    />

                                    <flux:button
                                        size="sm"
                                        variant="subtle"
                                        :icon="$company->isActive() ? 'pause' : 'play'"
                                        wire:click="toggleStatus({{ $company->id }})"
                                        wire:confirm="¿Deseas cambiar el estado de {{ $company->name }}?"
                                        aria-label="{{ $company->isActive() ? 'Desactivar' : 'Activar' }} {{ $company->name }}"
                                    />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-14 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <span class="flex size-12 items-center justify-center rounded-2xl bg-royal-50 dark:bg-royal-600/20 text-royal-700 dark:text-royal-300">
                                        <flux:icon.building-office-2 class="size-6" />
                                    </span>
                                    <p class="mt-4 font-medium text-navy-900 dark:text-zinc-100">Aún no hay empresas registradas</p>
                                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Crea la primera empresa para comenzar a organizar usuarios, áreas y servicios.</p>
                                    <flux:button class="mt-5" variant="primary" :href="route('companies.create')" wire:navigate>
                                        Crear empresa
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($companies->hasPages())
            <div class="border-t border-zinc-100 dark:border-zinc-800 px-5 py-4">
                <flux:pagination :paginator="$companies" />
            </div>
        @endif
    </div>
</div>
