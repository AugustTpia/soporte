<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="page-title">Cuentas de servicio</h1>
            <p class="page-subtitle">
                Cuentas Nextcloud y otros servicios de {{ $companyName ?: 'la empresa' }}.
            </p>
        </div>

        @can('create', \App\Accounts\Infrastructure\Persistence\Eloquent\Models\ServiceAccountModel::class)
            <flux:button :href="route('accounts.create')" variant="primary" icon="plus" wire:navigate>
                Nueva cuenta
            </flux:button>
        @endcan
    </div>

    <div class="surface-card p-4">
        <div class="grid gap-3 sm:grid-cols-[1fr_200px_200px]">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Buscar por usuario, servidor, persona o equipo..."
                aria-label="Buscar cuentas"
            />

            <flux:select wire:model.live="service" aria-label="Filtrar por servicio">
                <option value="">Todos los servicios</option>
                @foreach($serviceOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="status" aria-label="Filtrar por estado">
                <option value="">Todos los estados</option>
                @foreach($statusOptions as $value => $label)
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
                        <th class="px-5 py-3 font-semibold">Cuenta</th>
                        <th class="px-5 py-3 font-semibold">Servicio</th>
                        <th class="px-5 py-3 font-semibold">Persona</th>
                        <th class="px-5 py-3 font-semibold">Equipos</th>
                        <th class="px-5 py-3 font-semibold">Responsable</th>
                        <th class="px-5 py-3 font-semibold">Estado</th>
                        <th class="px-5 py-3 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse($accounts as $account)
                        <tr wire:key="account-{{ $account->id }}" class="transition hover:bg-royal-50/40 dark:hover:bg-zinc-800/60">
                            <td class="px-5 py-4">
                                <div class="flex items-start gap-3">
                                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-royal-50 dark:bg-royal-600/20 text-royal-700 dark:text-royal-300">
                                        <flux:icon.key class="size-5" />
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-navy-900 dark:text-zinc-100">{{ $account->username }}</p>
                                        <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $account->instance }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-zinc-600 dark:text-zinc-400">{{ $account->service->label() }}</td>
                            <td class="px-5 py-4 text-zinc-600 dark:text-zinc-400">{{ $account->ownerName ?: '—' }}</td>
                            <td class="px-5 py-4">
                                @php($activeLinks = $account->activeLinks())
                                @if($activeLinks === [])
                                    <span class="text-xs text-zinc-400">Sin equipos</span>
                                @else
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($activeLinks as $link)
                                            <flux:badge wire:key="link-{{ $link->id }}" color="zinc" size="sm">
                                                {{ $link->assetInternalCode }}
                                            </flux:badge>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-zinc-600 dark:text-zinc-400">{{ $account->responsibleUserName ?: '—' }}</td>
                            <td class="px-5 py-4">
                                <flux:badge :color="$account->status->color()" size="sm">{{ $account->status->label() }}</flux:badge>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    @if(auth()->user()->can('accounts.manage'))
                                        <flux:button
                                            size="sm"
                                            variant="subtle"
                                            icon="pencil-square"
                                            :href="route('accounts.edit', $account->id)"
                                            wire:navigate
                                            aria-label="Editar {{ $account->username }}"
                                        />
                                    @else
                                        <span class="text-xs text-zinc-400">Solo consulta</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-14 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <span class="flex size-12 items-center justify-center rounded-2xl bg-royal-50 dark:bg-royal-600/20 text-royal-700 dark:text-royal-300">
                                        <flux:icon.key class="size-6" />
                                    </span>
                                    <p class="mt-4 font-medium text-navy-900 dark:text-zinc-100">Aún no hay cuentas</p>
                                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                        Registra las cuentas de servicio y relaciónalas con los equipos del cliente.
                                    </p>
                                    @can('create', \App\Accounts\Infrastructure\Persistence\Eloquent\Models\ServiceAccountModel::class)
                                        <flux:button class="mt-5" variant="primary" :href="route('accounts.create')" wire:navigate>
                                            Crear cuenta
                                        </flux:button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($accounts->hasPages())
            <div class="border-t border-zinc-100 dark:border-zinc-800 px-5 py-4">
                <flux:pagination :paginator="$accounts" />
            </div>
        @endif
    </div>
</div>
