<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="mb-2 flex items-center gap-2 text-xs text-zinc-400">
                <a href="{{ route('accounts.index') }}" class="hover:text-royal-600" wire:navigate>Cuentas</a>
                <span>/</span>
                <span>{{ $isEditing ? 'Editar' : 'Nueva' }}</span>
            </div>
            <h1 class="page-title">{{ $isEditing ? 'Editar cuenta' : 'Nueva cuenta' }}</h1>
            <p class="page-subtitle">
                {{ $companyName ? 'Cuentas de servicio de '.$companyName.'.' : 'Registra una cuenta de servicio.' }}
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

    <div class="rounded-2xl border border-sky-200 dark:border-sky-500/30 bg-sky-50 dark:bg-sky-500/10 p-4 text-sm text-sky-800 dark:text-sky-300">
        <p class="font-medium">Las contraseñas no se almacenan aquí.</p>
        <p class="mt-1">
            Esta ficha conserva únicamente metadatos no sensibles. El acceso a secretos se implementará mediante una bóveda autorizada y auditada.
        </p>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="surface-card p-5 sm:p-6">
            <div class="mb-5">
                <h2 class="text-sm font-semibold text-navy-900 dark:text-zinc-100">Datos de la cuenta</h2>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Identifica el servicio, la instancia y el usuario.</p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <flux:select wire:model="service" label="Servicio" required>
                    @foreach($serviceOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </flux:select>

                <flux:input
                    wire:model="username"
                    label="Nombre de usuario"
                    placeholder="Ej. juan.perez"
                    maxlength="150"
                    required
                />

                <div class="sm:col-span-2">
                    <flux:input
                        wire:model="instance"
                        label="Instancia o servidor"
                        placeholder="Ej. cloud.empresa.com"
                        maxlength="255"
                        required
                    />
                </div>

                <flux:input
                    wire:model="ownerName"
                    label="Persona de la cuenta"
                    placeholder="Ej. Juan Pérez"
                    maxlength="150"
                />

                <flux:select wire:model="responsibleUserId" label="Responsable interno">
                    <option value="">Sin responsable</option>
                    @foreach($responsibleOptions as $user)
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
            <flux:textarea
                wire:model="notes"
                label="Notas no sensibles"
                placeholder="Indicaciones operativas, referencia interna o información útil. No escribas contraseñas ni tokens."
                rows="4"
            />
        </div>

        @if($isEditing && $account)
            @php($linkableAssets = array_values(array_filter($assetOptions, fn (array $asset) => $asset['is_linkable'])))

            <div class="surface-card p-5 sm:p-6">
                <div class="mb-5">
                    <h2 class="text-sm font-semibold text-navy-900 dark:text-zinc-100">Equipos vinculados</h2>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        Una cuenta puede pasar por varios equipos; el historial se conserva.
                    </p>
                </div>

                @if($activeLinks === [])
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">La cuenta no tiene equipos vinculados actualmente.</p>
                @else
                    <div class="space-y-3">
                        @foreach($activeLinks as $link)
                            <div wire:key="active-link-{{ $link->id }}" class="flex flex-col gap-3 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-medium text-navy-900 dark:text-zinc-100">{{ $link->assetInternalCode ?: 'Equipo eliminado' }}</p>
                                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $link->assetHostname ?: 'Sin hostname' }} · Vinculado {{ $link->linkedAt->format('d/m/Y H:i') }}
                                    </p>
                                </div>
                                <flux:button
                                    type="button"
                                    size="sm"
                                    variant="subtle"
                                    icon="x-mark"
                                    wire:click="unlinkAsset({{ $link->id }})"
                                    wire:confirm="¿Desvincular esta cuenta del equipo? El historial se conservará."
                                >
                                    Desvincular
                                </flux:button>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if($account->status === \App\Accounts\Domain\Enums\ServiceAccountStatus::Active)
                    <div class="mt-6 border-t border-zinc-100 dark:border-zinc-800 pt-5">
                        <div class="grid items-end gap-3 sm:grid-cols-[1fr_auto]">
                            <flux:select wire:model="linkAssetId" label="Vincular otro equipo">
                                <option value="">Selecciona un equipo</option>
                                @foreach($linkableAssets as $asset)
                                    <option value="{{ $asset['id'] }}">{{ $asset['label'] }}</option>
                                @endforeach
                            </flux:select>

                            <flux:button type="button" variant="primary" icon="link" wire:click="linkAsset" :disabled="$linkableAssets === []">
                                Vincular
                            </flux:button>
                        </div>

                        @if($linkableAssets === [])
                            <p class="mt-2 text-xs text-amber-600 dark:text-amber-400">
                                No hay equipos disponibles para vincular. Registra o activa un equipo primero.
                            </p>
                        @endif
                    </div>

                    @if($activeLinks !== [] && $linkableAssets !== [])
                        <div class="mt-6 border-t border-zinc-100 dark:border-zinc-800 pt-5">
                            <h3 class="text-sm font-semibold text-navy-900 dark:text-zinc-100">Sustituir equipo</h3>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                Cierra el vínculo anterior y registra el nuevo equipo sin perder trazabilidad.
                            </p>

                            <div class="mt-4 grid items-end gap-3 sm:grid-cols-[1fr_1fr_auto]">
                                <flux:select wire:model="transferFromAssetId" label="Equipo actual">
                                    <option value="">Selecciona el equipo actual</option>
                                    @foreach($activeLinks as $link)
                                        <option value="{{ $link->assetId }}">
                                            {{ $link->assetInternalCode ?: 'Equipo eliminado' }}{{ $link->assetHostname ? ' · '.$link->assetHostname : '' }}
                                        </option>
                                    @endforeach
                                </flux:select>

                                <flux:select wire:model="transferToAssetId" label="Equipo nuevo">
                                    <option value="">Selecciona el equipo nuevo</option>
                                    @foreach($linkableAssets as $asset)
                                        <option value="{{ $asset['id'] }}">{{ $asset['label'] }}</option>
                                    @endforeach
                                </flux:select>

                                <flux:button type="button" variant="primary" icon="arrow-right" wire:click="transferAsset">
                                    Transferir
                                </flux:button>
                            </div>
                        </div>
                    @endif
                @else
                    <p class="mt-4 text-xs text-amber-600 dark:text-amber-400">
                        La cuenta está {{ mb_strtolower($account->status->label()) }}. Puedes desvincular equipos, pero no crear vínculos nuevos.
                    </p>
                @endif
            </div>

            @if($links !== [])
                <div class="surface-card p-5 sm:p-6">
                    <div class="mb-5">
                        <h2 class="text-sm font-semibold text-navy-900 dark:text-zinc-100">Historial de equipos</h2>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Vínculos actuales y anteriores de la cuenta.</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800 text-sm">
                            <thead class="text-left text-[11px] uppercase tracking-[0.12em] text-zinc-500 dark:text-zinc-400">
                                <tr>
                                    <th class="py-3 pe-4 font-semibold">Equipo</th>
                                    <th class="py-3 pe-4 font-semibold">Vinculado</th>
                                    <th class="py-3 pe-4 font-semibold">Desvinculado</th>
                                    <th class="py-3 pe-4 font-semibold">Motivo</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @foreach($links as $link)
                                    <tr wire:key="history-link-{{ $link->id }}">
                                        <td class="py-3 pe-4">
                                            <p class="text-zinc-700 dark:text-zinc-300">{{ $link->assetInternalCode ?: 'Equipo eliminado' }}</p>
                                            @if($link->assetHostname)
                                                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $link->assetHostname }}</p>
                                            @endif
                                        </td>
                                        <td class="py-3 pe-4 text-zinc-600 dark:text-zinc-400">{{ $link->linkedAt->format('d/m/Y H:i') }}</td>
                                        <td class="py-3 pe-4 text-zinc-600 dark:text-zinc-400">
                                            {{ $link->unlinkedAt?->format('d/m/Y H:i') ?? 'Vigente' }}
                                        </td>
                                        <td class="py-3 pe-4 text-zinc-600 dark:text-zinc-400">
                                            {{ $link->unlinkReason ?: $link->reason ?: '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endif

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
            <flux:button :href="route('accounts.index')" variant="ghost" wire:navigate>
                Cancelar
            </flux:button>

            <flux:button variant="primary" type="submit" icon="check">
                {{ $isEditing ? 'Guardar cambios' : 'Crear cuenta' }}
            </flux:button>
        </div>
    </form>
</div>
