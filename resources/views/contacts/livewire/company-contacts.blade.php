<div class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-sm font-semibold text-navy-900 dark:text-zinc-100">Contactos</h2>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Personas de contacto de la empresa y sus sedes.</p>
        </div>

        @if($canManage)
            <flux:button size="sm" variant="primary" icon="plus" wire:click="openCreate">
                Nuevo contacto
            </flux:button>
        @endif
    </div>

    <div class="surface-card p-4">
        <div class="grid gap-3 sm:grid-cols-[1fr_180px]">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Buscar por nombre, correo, teléfono o puesto..."
                aria-label="Buscar contactos"
            />

            <flux:select wire:model.live="statusFilter" aria-label="Filtrar contactos por estado">
                <option value="">Todos los estados</option>
                <option value="active">Activos</option>
                <option value="inactive">Inactivos</option>
            </flux:select>
        </div>
    </div>

    <div class="surface-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                <thead class="bg-zinc-50/80 text-left text-[11px] uppercase tracking-[0.12em] text-zinc-500 dark:bg-zinc-800/60 dark:text-zinc-400">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Contacto</th>
                        <th class="px-5 py-3 font-semibold">Medios</th>
                        <th class="px-5 py-3 font-semibold">Sede</th>
                        <th class="px-5 py-3 font-semibold">Estado</th>
                        @if($canManage)
                            <th class="px-5 py-3 text-right font-semibold">Acciones</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse($contacts as $contact)
                        <tr wire:key="contact-{{ $contact->id }}" class="transition hover:bg-royal-50/40 dark:hover:bg-zinc-800/60">
                            <td class="px-5 py-4">
                                <div class="flex items-start gap-3">
                                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-royal-50 text-royal-700 dark:bg-royal-600/20 dark:text-royal-300">
                                        <flux:icon.user class="size-5" />
                                    </span>
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="font-semibold text-navy-900 dark:text-zinc-100">{{ $contact->name }}</p>
                                            @if($contact->isPrimary)
                                                <flux:badge color="blue" size="sm">Principal</flux:badge>
                                            @endif
                                        </div>
                                        <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $contact->position ?: 'Sin puesto registrado' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="space-y-1 text-xs text-zinc-600 dark:text-zinc-400">
                                    @if($contact->email)
                                        <p class="break-all">{{ $contact->email }}</p>
                                    @endif
                                    @if($contact->phone)
                                        <p>Tel. {{ $contact->phone }}</p>
                                    @endif
                                    @if($contact->whatsapp)
                                        <p>WhatsApp {{ $contact->whatsapp }}</p>
                                    @endif
                                    @if(! $contact->email && ! $contact->phone && ! $contact->whatsapp)
                                        <p>Sin medios registrados</p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-4 text-zinc-600 dark:text-zinc-400">{{ $contact->locationName ?: 'Todas' }}</td>
                            <td class="px-5 py-4">
                                <flux:badge :color="$contact->isActive ? 'green' : 'zinc'" size="sm">
                                    {{ $contact->isActive ? 'Activo' : 'Inactivo' }}
                                </flux:badge>
                            </td>
                            @if($canManage)
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <flux:button
                                            size="sm"
                                            variant="subtle"
                                            icon="pencil-square"
                                            wire:click="openEdit({{ $contact->id }})"
                                            aria-label="Editar {{ $contact->name }}"
                                        />

                                        <flux:button
                                            size="sm"
                                            variant="subtle"
                                            :icon="$contact->isActive ? 'pause' : 'play'"
                                            wire:click="toggleStatus({{ $contact->id }})"
                                            wire:confirm="¿Deseas {{ $contact->isActive ? 'desactivar' : 'activar' }} a {{ $contact->name }}?"
                                            aria-label="{{ $contact->isActive ? 'Desactivar' : 'Activar' }} {{ $contact->name }}"
                                        />
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canManage ? 5 : 4 }}" class="px-5 py-12 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <span class="flex size-12 items-center justify-center rounded-2xl bg-royal-50 text-royal-700 dark:bg-royal-600/20 dark:text-royal-300">
                                        <flux:icon.user-group class="size-6" />
                                    </span>
                                    <p class="mt-4 font-medium text-navy-900 dark:text-zinc-100">Sin contactos registrados</p>
                                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ $search !== '' ? 'No hay coincidencias con la búsqueda.' : 'Agrega las personas de contacto de este cliente.' }}
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($canManage)
        <flux:modal name="contact-form" class="md:w-2/3">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $contactId === null ? 'Nuevo contacto' : 'Editar contacto' }}</flux:heading>
                <flux:text class="mt-1">Datos de la persona que atiende temas operativos con el cliente.</flux:text>
            </div>

            @if($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <flux:input wire:model="name" label="Nombre completo" maxlength="150" required autofocus />
                </div>

                <flux:input wire:model="position" label="Puesto" maxlength="100" placeholder="Ej. Encargado de sistemas" />

                <flux:select wire:model="locationId" label="Sede">
                    <option value="">Todas las sedes</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="email" type="email" label="Correo" maxlength="150" />
                <flux:input wire:model="phone" type="tel" label="Teléfono" maxlength="30" />
                <flux:input wire:model="whatsapp" type="tel" label="WhatsApp" maxlength="30" />

                <div class="sm:col-span-2">
                    <flux:textarea wire:model="notes" label="Notas" rows="3" maxlength="2000" placeholder="Horario, indicaciones o información operativa no sensible." />
                </div>

                <div class="sm:col-span-2 grid gap-3 rounded-xl border border-zinc-200 bg-zinc-50/60 p-4 sm:grid-cols-2 dark:border-zinc-800 dark:bg-zinc-800/40">
                    <flux:checkbox wire:model.live="isPrimary" label="Contacto principal" description="Será el contacto sugerido para nuevas operaciones." />
                    <flux:checkbox wire:model.live="isActive" label="Contacto activo" description="Los contactos inactivos se conservan para historial." />
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <flux:button type="button" variant="ghost" wire:click="closeForm">Cancelar</flux:button>
                <flux:button type="submit" variant="primary" icon="check">
                    {{ $contactId === null ? 'Crear contacto' : 'Guardar cambios' }}
                </flux:button>
            </div>
        </form>
        </flux:modal>
    @endif
</div>
