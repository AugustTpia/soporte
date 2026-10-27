<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="page-title">Credenciales</h1>
            <p class="page-subtitle">
                Metadatos y secretos protegidos de {{ $companyName ?: 'la empresa' }}.
            </p>
        </div>

        @can('create', \App\Credentials\Infrastructure\Persistence\Eloquent\Models\CredentialModel::class)
            <flux:button :href="route('credentials.create')" variant="primary" icon="plus" wire:navigate>
                Nueva credencial
            </flux:button>
        @endcan
    </div>

    <div class="rounded-2xl border border-amber-200 dark:border-amber-500/30 bg-amber-50 dark:bg-amber-500/10 p-4 text-sm text-amber-800 dark:text-amber-300">
        <p class="font-medium">Los secretos se revelan solo con autorización y reautenticación.</p>
        <p class="mt-1">
            Esta pantalla nunca muestra contraseñas. Cada acceso queda auditado sin registrar el valor del secreto.
        </p>
    </div>

    <div class="surface-card p-4">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_240px_190px_190px]">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Buscar por etiqueta, usuario o instancia..."
                aria-label="Buscar credenciales"
            />

            <flux:select wire:model.live="account" aria-label="Filtrar por cuenta">
                <option value="">Todas las cuentas</option>
                @foreach($accountOptions as $option)
                    <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="kind" aria-label="Filtrar por tipo">
                <option value="">Todos los tipos</option>
                @foreach($kindOptions as $value => $label)
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
                        <th class="px-5 py-3 font-semibold">Credencial</th>
                        <th class="px-5 py-3 font-semibold">Cuenta</th>
                        <th class="px-5 py-3 font-semibold">Tipo</th>
                        <th class="px-5 py-3 font-semibold">Secreto</th>
                        <th class="px-5 py-3 font-semibold">Última rotación</th>
                        <th class="px-5 py-3 font-semibold">Estado</th>
                        <th class="px-5 py-3 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse($credentials as $credential)
                        <tr wire:key="credential-{{ $credential->id }}" class="transition hover:bg-royal-50/40 dark:hover:bg-zinc-800/60">
                            <td class="px-5 py-4">
                                <div class="flex items-start gap-3">
                                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-royal-50 dark:bg-royal-600/20 text-royal-700 dark:text-royal-300">
                                        <flux:icon.lock-closed class="size-5" />
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-navy-900 dark:text-zinc-100">{{ $credential->label }}</p>
                                        <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Ref. #{{ $credential->id }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <p class="text-zinc-700 dark:text-zinc-300">{{ $credential->accountUsername }}</p>
                                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $credential->accountInstance ?: 'Sin instancia' }}</p>
                            </td>
                            <td class="px-5 py-4 text-zinc-600 dark:text-zinc-400">{{ $credential->kind->label() }}</td>
                            <td class="px-5 py-4">
                                @if($credential->hasSecret)
                                    <flux:badge color="green" size="sm">Configurado</flux:badge>
                                @else
                                    <flux:badge color="amber" size="sm">Pendiente</flux:badge>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-zinc-600 dark:text-zinc-400">
                                {{ $credential->lastRotatedAt?->format('d/m/Y H:i') ?? '—' }}
                            </td>
                            <td class="px-5 py-4">
                                <flux:badge :color="$credential->status->color()" size="sm">{{ $credential->status->label() }}</flux:badge>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    @if(auth()->user()->can('credentials.manage'))
                                        <flux:button
                                            size="sm"
                                            variant="subtle"
                                            icon="pencil-square"
                                            :href="route('credentials.edit', $credential->id)"
                                            wire:navigate
                                            aria-label="Editar {{ $credential->label }}"
                                        />
                                    @else
                                        <flux:button
                                            size="sm"
                                            variant="subtle"
                                            icon="eye"
                                            :href="route('credentials.edit', $credential->id)"
                                            wire:navigate
                                            aria-label="Ver {{ $credential->label }}"
                                        />
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-14 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <span class="flex size-12 items-center justify-center rounded-2xl bg-royal-50 dark:bg-royal-600/20 text-royal-700 dark:text-royal-300">
                                        <flux:icon.lock-closed class="size-6" />
                                    </span>
                                    <p class="mt-4 font-medium text-navy-900 dark:text-zinc-100">Aún no hay credenciales</p>
                                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                        Registra referencias de credenciales para las cuentas de servicio del cliente.
                                    </p>
                                    @can('create', \App\Credentials\Infrastructure\Persistence\Eloquent\Models\CredentialModel::class)
                                        <flux:button class="mt-5" variant="primary" :href="route('credentials.create')" wire:navigate>
                                            Crear credencial
                                        </flux:button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($credentials->hasPages())
            <div class="border-t border-zinc-100 dark:border-zinc-800 px-5 py-4">
                <flux:pagination :paginator="$credentials" />
            </div>
        @endif
    </div>
</div>
