<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="mb-2 flex items-center gap-2 text-xs text-zinc-400">
                <a href="{{ route('tickets.index') }}" class="hover:text-royal-600" wire:navigate>Tickets</a>
                <span>/</span>
                <span>{{ $isEditing ? 'Editar' : 'Nuevo' }}</span>
            </div>
            <h1 class="page-title">{{ $isEditing ? 'Editar ticket' : 'Nuevo ticket' }}</h1>
            <p class="page-subtitle">
                {{ $companyName ? 'Solicitud de soporte de '.$companyName.'.' : 'Registra una solicitud de soporte.' }}
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

    <form wire:submit="save" class="space-y-6">
        <div class="surface-card p-5 sm:p-6">
            <div class="mb-5">
                <h2 class="text-sm font-semibold text-navy-900 dark:text-zinc-100">Solicitud</h2>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    Describe la necesidad o incidencia reportada por el cliente.
                </p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <flux:select wire:model="category" label="Categoría" required>
                    @foreach($categoryOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="priority" label="Prioridad" required>
                    @foreach($priorityOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </flux:select>

                <div class="sm:col-span-2">
                    <flux:input
                        wire:model="subject"
                        label="Asunto"
                        placeholder="Ej. Equipo de recepción sin acceso a Nextcloud"
                        maxlength="180"
                        required
                        autofocus
                    />
                </div>

                <div class="sm:col-span-2">
                    <flux:textarea
                        wire:model="description"
                        label="Descripción"
                        placeholder="Detalla el problema, lo que se solicitó, el contexto y cualquier dato útil para el técnico."
                        rows="6"
                        required
                    />
                </div>
            </div>
        </div>

        <div class="surface-card p-5 sm:p-6">
            <div class="mb-5">
                <h2 class="text-sm font-semibold text-navy-900 dark:text-zinc-100">Solicitante</h2>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    Datos de contacto de la persona que reporta o solicita el servicio.
                </p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                @if(auth()->user()->can('contacts.view'))
                    <div class="sm:col-span-2">
                        <flux:select wire:model.live="clientContactId" label="Contacto del cliente">
                            <option value="">Sin contacto registrado</option>
                            @foreach($contactOptions as $contact)
                                <option value="{{ $contact['id'] }}">
                                    {{ $contact['name'] }}{{ $contact['contact'] ? ' · '.$contact['contact'] : '' }}
                                </option>
                            @endforeach
                        </flux:select>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            Al seleccionar un contacto se copian sus datos como referencia. El ticket conserva una instantánea histórica.
                        </p>
                    </div>
                @endif

                <flux:input wire:model="requesterName" label="Nombre" placeholder="Ej. Juan Pérez" maxlength="150" />
                <flux:input wire:model="requesterContact" label="Medio de contacto" placeholder="Teléfono, correo o extensión" maxlength="150" />
            </div>
        </div>

        <div class="surface-card p-5 sm:p-6">
            <div class="mb-5">
                <h2 class="text-sm font-semibold text-navy-900 dark:text-zinc-100">Vínculos opcionales</h2>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    Puedes relacionar el ticket con una sede, un equipo o una cuenta de servicio.
                </p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <flux:select wire:model="locationId" label="Ubicación">
                    <option value="">Sin ubicación</option>
                    @foreach($locationOptions as $location)
                        <option value="{{ $location['id'] }}">{{ $location['name'] }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="assetId" label="Activo">
                    <option value="">Sin activo</option>
                    @foreach($assetOptions as $asset)
                        <option value="{{ $asset['id'] }}">{{ $asset['label'] }}</option>
                    @endforeach
                </flux:select>

                <div class="sm:col-span-2">
                    <flux:select wire:model="serviceAccountId" label="Cuenta de servicio">
                        <option value="">Sin cuenta de servicio</option>
                        @foreach($accountOptions as $account)
                            <option value="{{ $account['id'] }}">{{ $account['label'] }}</option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        </div>

        <div class="surface-card p-5 sm:p-6">
            <div class="mb-5">
                <h2 class="text-sm font-semibold text-navy-900 dark:text-zinc-100">Seguimiento</h2>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    La fecha compromiso es opcional y sirve para priorizar la agenda del equipo.
                </p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <flux:input wire:model="dueAt" label="Fecha compromiso" type="datetime-local" />
            </div>
        </div>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
            <flux:button :href="$isEditing ? route('tickets.show', $ticketId) : route('tickets.index')" variant="ghost" wire:navigate>
                Cancelar
            </flux:button>

            <flux:button variant="primary" type="submit" icon="check">
                {{ $isEditing ? 'Guardar cambios' : 'Crear ticket' }}
            </flux:button>
        </div>
    </form>
</div>
