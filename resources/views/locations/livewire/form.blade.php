<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="mb-2 flex items-center gap-2 text-xs text-zinc-400">
                <a href="{{ route('locations.index') }}" class="hover:text-royal-600" wire:navigate>Ubicaciones</a>
                <span>/</span>
                <span>{{ $isEditing ? 'Editar' : 'Nueva' }}</span>
            </div>
            <h1 class="page-title">{{ $isEditing ? 'Editar ubicación' : 'Nueva ubicación' }}</h1>
            <p class="page-subtitle">
                {{ $companyName ? 'Dirección operativa de '.$companyName.'.' : 'Registra una dirección operativa.' }}
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
                <h2 class="text-sm font-semibold text-navy-900 dark:text-zinc-100">Datos generales</h2>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Identifica la sucursal o dirección dentro de la empresa.</p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <flux:input
                        wire:model="name"
                        label="Nombre de la ubicación"
                        placeholder="Ej. Matriz, Sucursal Norte, Almacén central"
                        maxlength="120"
                        required
                        autofocus
                    />
                </div>

                <flux:input
                    wire:model="phone"
                    label="Teléfono"
                    placeholder="Ej. 55 1234 5678"
                    maxlength="30"
                    type="tel"
                />

                <flux:input
                    wire:model="country"
                    label="País"
                    maxlength="80"
                    required
                />
            </div>
        </div>

        <div class="surface-card p-5 sm:p-6">
            <div class="mb-5">
                <h2 class="text-sm font-semibold text-navy-900 dark:text-zinc-100">Dirección</h2>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    Calle, número exterior y ciudad son obligatorios.
                </p>
            </div>

            <div class="grid gap-5 sm:grid-cols-6">
                <div class="sm:col-span-4">
                    <flux:input
                        wire:model="street"
                        label="Calle"
                        placeholder="Ej. Av. Reforma"
                        maxlength="180"
                        required
                    />
                </div>

                <div class="sm:col-span-1">
                    <flux:input
                        wire:model="exteriorNumber"
                        label="No. exterior"
                        placeholder="Ej. 123"
                        maxlength="20"
                        required
                    />
                </div>

                <div class="sm:col-span-1">
                    <flux:input
                        wire:model="interiorNumber"
                        label="No. interior"
                        placeholder="Opcional"
                        maxlength="20"
                    />
                </div>

                <div class="sm:col-span-3">
                    <flux:input
                        wire:model="neighborhood"
                        label="Colonia"
                        placeholder="Ej. Centro"
                        maxlength="120"
                    />
                </div>

                <div class="sm:col-span-3">
                    <flux:input
                        wire:model="city"
                        label="Ciudad"
                        placeholder="Ej. Ciudad de México"
                        maxlength="120"
                        required
                    />
                </div>

                <div class="sm:col-span-3">
                    <flux:input
                        wire:model="state"
                        label="Estado"
                        placeholder="Ej. CDMX"
                        maxlength="120"
                    />
                </div>

                <div class="sm:col-span-3">
                    <flux:input
                        wire:model="postalCode"
                        label="Código postal"
                        placeholder="Ej. 06000"
                        maxlength="12"
                    />
                </div>
            </div>
        </div>

        <div class="surface-card p-5 sm:p-6">
            <div class="grid gap-5">
                <flux:textarea
                    wire:model="notes"
                    label="Notas"
                    placeholder="Referencias de acceso, horario, indicaciones o información operativa."
                    rows="4"
                />

                <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-800/40 p-4">
                    <flux:checkbox wire:model="isActive" label="Ubicación activa" description="Las ubicaciones inactivas se conservan para el historial, pero no se consideran para nuevas operaciones." />
                </div>
            </div>
        </div>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
            <flux:button :href="route('locations.index')" variant="ghost" wire:navigate>
                Cancelar
            </flux:button>

            <flux:button variant="primary" type="submit" icon="check">
                {{ $isEditing ? 'Guardar cambios' : 'Crear ubicación' }}
            </flux:button>
        </div>
    </form>
</div>
