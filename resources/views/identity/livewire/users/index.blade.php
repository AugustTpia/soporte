<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="page-title">Usuarios</h1>
            <p class="page-subtitle">Administra accesos, roles y departamentos del equipo.</p>
        </div>

        @can('create', \App\Identity\Infrastructure\Persistence\Eloquent\User::class)
            <flux:button :href="route('users.create')" variant="primary" icon="plus" wire:navigate>
                Nuevo usuario
            </flux:button>
        @endcan
    </div>

    <div class="surface-card p-4">
        <div class="grid gap-3 md:grid-cols-[1fr_180px_200px]">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Buscar por nombre, correo o puesto..."
                aria-label="Buscar usuarios"
            />

            <flux:select wire:model.live="status" aria-label="Filtrar por estado">
                <option value="">Todos los estados</option>
                <option value="active">Activos</option>
                <option value="inactive">Inactivos</option>
            </flux:select>

            <flux:select wire:model.live="role" aria-label="Filtrar por rol">
                <option value="">Todos los roles</option>
                @foreach($roles as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <div class="surface-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800 text-sm">
                <thead class="bg-zinc-50/80 dark:bg-zinc-800/60 text-left text-[11px] uppercase tracking-[0.12em] text-zinc-500 dark:text-zinc-400">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Usuario</th>
                        <th class="px-5 py-3 font-semibold">Rol</th>
                        <th class="px-5 py-3 font-semibold">Departamentos</th>
                        @if($global)
                            <th class="px-5 py-3 font-semibold">Empresa</th>
                        @endif
                        <th class="px-5 py-3 font-semibold">Estado</th>
                        <th class="px-5 py-3 font-semibold">Último acceso</th>
                        <th class="px-5 py-3 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse($users as $user)
                        <tr class="transition hover:bg-royal-50/40 dark:hover:bg-zinc-800/60">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-royal-600 text-xs font-semibold text-white">
                                        {{ $user->initials() }}
                                    </span>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <p class="truncate font-semibold text-navy-900 dark:text-zinc-100">{{ $user->name }}</p>
                                            @if(auth()->id() === $user->id)
                                                <flux:badge color="blue" size="sm">Tú</flux:badge>
                                            @endif
                                        </div>
                                        <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $user->email }}</p>
                                        @if($user->jobTitle)
                                            <p class="mt-0.5 truncate text-[11px] text-zinc-400">{{ $user->jobTitle }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @forelse($user->roles as $role)
                                        <flux:badge color="zinc" size="sm">
                                            {{ \App\Identity\Domain\Enums\RoleName::tryFrom($role)?->label() ?? $role }}
                                        </flux:badge>
                                    @empty
                                        <span class="text-xs text-zinc-400">Sin rol</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                @if($user->departmentNames !== [])
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($user->departmentNames as $department)
                                            <span class="rounded-md bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 text-[11px] text-zinc-600 dark:text-zinc-400">{{ $department }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-zinc-400">Toda la empresa</span>
                                @endif
                            </td>
                            @if($global)
                                <td class="px-5 py-4 text-zinc-600 dark:text-zinc-400">
                                    {{ $user->companyName ?? 'Global' }}
                                </td>
                            @endif
                            <td class="px-5 py-4">
                                <flux:badge :color="$user->isActive() ? 'green' : 'zinc'" size="sm">
                                    {{ $user->isActive() ? 'Activo' : 'Inactivo' }}
                                </flux:badge>
                            </td>
                            <td class="px-5 py-4">
                                <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $user->lastLoginAt?->format('d/m/Y H:i') ?? 'Nunca' }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    @if($this->canEdit($user))
                                        <flux:button
                                            size="sm"
                                            variant="subtle"
                                            icon="pencil-square"
                                            :href="route('users.edit', $user->id)"
                                            wire:navigate
                                            aria-label="Editar {{ $user->name }}"
                                        />
                                    @endif

                                    @if($this->canToggle($user))
                                        <flux:button
                                            size="sm"
                                            variant="subtle"
                                            :icon="$user->isActive() ? 'pause' : 'play'"
                                            wire:click="toggleStatus({{ $user->id }})"
                                            wire:confirm="¿Deseas cambiar el estado de {{ $user->name }}?"
                                            aria-label="{{ $user->isActive() ? 'Desactivar' : 'Activar' }} {{ $user->name }}"
                                        />
                                    @endif

                                    @if(! $this->canEdit($user) && ! $this->canToggle($user))
                                        <span class="text-xs text-zinc-400">—</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $global ? 7 : 6 }}" class="px-5 py-14 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <span class="flex size-12 items-center justify-center rounded-2xl bg-royal-50 dark:bg-royal-600/20 text-royal-700 dark:text-royal-300">
                                        <flux:icon.users class="size-6" />
                                    </span>
                                    <p class="mt-4 font-medium text-navy-900 dark:text-zinc-100">No se encontraron usuarios</p>
                                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Ajusta los filtros o crea un nuevo usuario para comenzar.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="border-t border-zinc-100 dark:border-zinc-800 px-5 py-4">
                <flux:pagination :paginator="$users" />
            </div>
        @endif
    </div>
</div>
