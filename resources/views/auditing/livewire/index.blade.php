<div class="space-y-6">
    <div>
        <h1 class="page-title">Auditoría</h1>
        <p class="page-subtitle">
            Registro de actividad administrativa y operativa. Los secretos y contraseñas nunca se almacenan aquí.
        </p>
    </div>

    <div class="surface-card p-4">
        <div class="grid gap-3 lg:grid-cols-[1fr_190px_160px_160px_auto]">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Buscar por descripción, evento o usuario..."
                aria-label="Buscar auditoría"
            />

            <flux:select wire:model.live="event" aria-label="Filtrar por evento">
                <option value="">Todos los eventos</option>
                @foreach($events as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </flux:select>

            <flux:input wire:model.live="from" type="date" label="" aria-label="Desde" />
            <flux:input wire:model.live="to" type="date" label="" aria-label="Hasta" />

            <flux:button variant="subtle" icon="x-mark" wire:click="clearFilters">
                Limpiar
            </flux:button>
        </div>
    </div>

    <div class="surface-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800 text-sm">
                <thead class="bg-zinc-50/80 dark:bg-zinc-800/60 text-left text-[11px] uppercase tracking-[0.12em] text-zinc-500 dark:text-zinc-400">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Fecha</th>
                        <th class="px-5 py-3 font-semibold">Usuario</th>
                        <th class="px-5 py-3 font-semibold">Evento</th>
                        <th class="px-5 py-3 font-semibold">Descripción</th>
                        @if($global)
                            <th class="px-5 py-3 font-semibold">Empresa</th>
                        @endif
                        <th class="px-5 py-3 font-semibold">Origen</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse($logs as $log)
                        <tr class="align-top transition hover:bg-royal-50/30 dark:hover:bg-zinc-800/40">
                            <td class="whitespace-nowrap px-5 py-4 text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $log->createdAt->format('d/m/Y') }}
                                <span class="block text-zinc-400">{{ $log->createdAt->format('H:i') }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-medium text-navy-900 dark:text-zinc-100">{{ $log->actorName ?? 'Sistema' }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <flux:badge color="zinc" size="sm">
                                    {{ $events[$log->event] ?? $log->event }}
                                </flux:badge>
                            </td>
                            <td class="max-w-md px-5 py-4 text-zinc-600 dark:text-zinc-400">
                                <p>{{ $log->description }}</p>

                                @if($log->oldValues || $log->newValues || $log->metadata)
                                    <details class="mt-2">
                                        <summary class="cursor-pointer text-xs font-medium text-royal-600 hover:text-royal-700 dark:text-royal-300">
                                            Ver detalle del cambio
                                        </summary>
                                        <div class="mt-2 space-y-2 rounded-xl bg-zinc-50 dark:bg-zinc-800/50 p-3">
                                            @if($log->oldValues)
                                                <div>
                                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400">Antes</p>
                                                    <pre class="mt-1 overflow-x-auto whitespace-pre-wrap break-words text-[11px] text-zinc-600 dark:text-zinc-400">{{ json_encode($log->oldValues, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                                </div>
                                            @endif

                                            @if($log->newValues)
                                                <div>
                                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400">Después</p>
                                                    <pre class="mt-1 overflow-x-auto whitespace-pre-wrap break-words text-[11px] text-zinc-600 dark:text-zinc-400">{{ json_encode($log->newValues, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                                </div>
                                            @endif

                                            @if($log->metadata)
                                                <div>
                                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400">Metadatos</p>
                                                    <pre class="mt-1 overflow-x-auto whitespace-pre-wrap break-words text-[11px] text-zinc-600 dark:text-zinc-400">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                                </div>
                                            @endif
                                        </div>
                                    </details>
                                @endif
                            </td>
                            @if($global)
                                <td class="px-5 py-4 text-zinc-600 dark:text-zinc-400">
                                    {{ $log->companyName ?? 'Global' }}
                                </td>
                            @endif
                            <td class="px-5 py-4">
                                <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $log->ipAddress ?? '—' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $global ? 6 : 5 }}" class="px-5 py-14 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <span class="flex size-12 items-center justify-center rounded-2xl bg-royal-50 dark:bg-royal-600/20 text-royal-700 dark:text-royal-300">
                                        <flux:icon.shield-check class="size-6" />
                                    </span>
                                    <p class="mt-4 font-medium text-navy-900 dark:text-zinc-100">Sin actividad registrada</p>
                                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Los cambios administrativos aparecerán aquí con su trazabilidad.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="border-t border-zinc-100 dark:border-zinc-800 px-5 py-4">
                <flux:pagination :paginator="$logs" />
            </div>
        @endif
    </div>
</div>
