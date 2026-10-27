<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="mb-2 flex items-center gap-2 text-xs text-zinc-400">
                <a href="{{ route('companies.index') }}" class="hover:text-royal-600" wire:navigate>Empresas</a>
                <span>/</span>
                <span>{{ $isEditing ? 'Editar' : 'Nueva empresa' }}</span>
            </div>
            <h1 class="page-title">{{ $isEditing ? 'Editar empresa' : 'Nueva empresa' }}</h1>
            <p class="page-subtitle">
                {{ $isEditing ? 'Actualiza la información general y de contacto de la empresa.' : 'Registra una empresa para comenzar a administrar su operación.' }}
            </p>
        </div>

        @if($isEditing)
            <flux:badge :color="$status === 'active' ? 'green' : 'zinc'" size="sm">
                {{ $status === 'active' ? 'Empresa activa' : 'Empresa inactiva' }}
            </flux:badge>
        @endif
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
            <div class="flex items-start gap-3 border-b border-zinc-100 dark:border-zinc-800 pb-4">
                <span class="flex size-9 items-center justify-center rounded-xl bg-royal-50 dark:bg-royal-600/20 text-royal-700 dark:text-royal-300">
                    <flux:icon.identification class="size-5" />
                </span>
                <div>
                    <h2 class="font-semibold text-navy-900 dark:text-zinc-100">Datos de la empresa</h2>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Identidad fiscal y comercial.</p>
                </div>
            </div>

            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <flux:input wire:model="name" label="Nombre comercial" placeholder="Ej. Grupo Industrial del Norte" required autofocus />
                </div>

                <flux:input wire:model="legalName" label="Razón social" placeholder="Nombre legal completo" />

                <flux:input wire:model="taxId" label="RFC / Identificación fiscal" placeholder="Opcional" />

                <flux:input wire:model="industry" label="Giro o industria" placeholder="Ej. Manufactura, salud, retail" />

                <flux:input wire:model="email" label="Correo principal" type="email" placeholder="contacto@empresa.com" />

                <flux:input wire:model="phone" label="Teléfono" placeholder="55 0000 0000" />

                <flux:input wire:model="website" label="Sitio web" type="url" placeholder="https://empresa.com" />
            </div>
        </div>

        <div class="surface-card p-5 sm:p-6">
            <div class="flex items-start gap-3 border-b border-zinc-100 dark:border-zinc-800 pb-4">
                <span class="flex size-9 items-center justify-center rounded-xl bg-gold-400/20 text-navy-900 dark:text-zinc-100">
                    <flux:icon.globe-alt class="size-5" />
                </span>
                <div>
                    <h2 class="font-semibold text-navy-900 dark:text-zinc-100">Configuración regional</h2>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Zona horaria e idioma para la operación de esta empresa.</p>
                </div>
            </div>

            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <flux:select wire:model="timezone" label="Zona horaria">
                    @foreach([
                        'America/Mexico_City' => 'Centro de México',
                        'America/Cancun' => 'Cancún',
                        'America/Monterrey' => 'Monterrey',
                        'America/Chihuahua' => 'Chihuahua',
                        'America/Hermosillo' => 'Hermosillo',
                        'America/Tijuana' => 'Tijuana',
                        'UTC' => 'UTC',
                    ] as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="locale" label="Idioma">
                    <option value="es_MX">Español (México)</option>
                    <option value="en_US">English (US)</option>
                </flux:select>
            </div>
        </div>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2">
                <flux:button :href="route('companies.index')" variant="ghost" wire:navigate>
                    Cancelar
                </flux:button>

                @if($isEditing)
                    <flux:button
                        type="button"
                        variant="subtle"
                        :icon="$status === 'active' ? 'pause' : 'play'"
                        wire:click="toggleStatus"
                        wire:confirm="¿Deseas cambiar el estado de esta empresa?"
                    >
                        {{ $status === 'active' ? 'Desactivar' : 'Activar' }}
                    </flux:button>
                @endif
            </div>

            <flux:button variant="primary" type="submit" icon="check">
                {{ $isEditing ? 'Guardar cambios' : 'Crear empresa' }}
            </flux:button>
        </div>
    </form>
</div>
