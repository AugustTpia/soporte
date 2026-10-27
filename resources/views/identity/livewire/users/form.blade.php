<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="mb-2 flex items-center gap-2 text-xs text-zinc-400">
                <a href="{{ route('users.index') }}" class="hover:text-royal-600" wire:navigate>Usuarios</a>
                <span>/</span>
                <span>{{ $isEditing ? 'Editar' : 'Nuevo' }}</span>
            </div>
            <h1 class="page-title">{{ $isEditing ? 'Editar usuario' : 'Nuevo usuario' }}</h1>
            <p class="page-subtitle">
                Define sus datos de acceso, rol y áreas de trabajo.
            </p>
        </div>

        @if($isEditing)
            <flux:badge color="zinc" size="sm">Cuenta existente</flux:badge>
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

    <form wire:submit="save" class="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
        <div class="space-y-6">
            <div class="surface-card p-5 sm:p-6">
                <div class="flex items-start gap-3 border-b border-zinc-100 dark:border-zinc-800 pb-4">
                    <span class="flex size-9 items-center justify-center rounded-xl bg-royal-50 dark:bg-royal-600/20 text-royal-700 dark:text-royal-300">
                        <flux:icon.identification class="size-5" />
                    </span>
                    <div>
                        <h2 class="font-semibold text-navy-900 dark:text-zinc-100">Datos personales</h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Información de contacto y puesto.</p>
                    </div>
                </div>

                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <flux:input wire:model="name" label="Nombre completo" placeholder="Nombre y apellidos" required autofocus />
                    </div>

                    <div class="sm:col-span-2">
                        <flux:input wire:model="email" label="Correo electrónico" type="email" placeholder="nombre@empresa.com" required autocomplete="email" />
                    </div>

                    <flux:input wire:model="jobTitle" label="Puesto" placeholder="Ej. Técnico de soporte" />

                    <flux:input wire:model="phone" label="Teléfono" placeholder="55 0000 0000" />
                </div>
            </div>

            <div class="surface-card p-5 sm:p-6">
                <div class="flex items-start gap-3 border-b border-zinc-100 dark:border-zinc-800 pb-4">
                    <span class="flex size-9 items-center justify-center rounded-xl bg-gold-400/20 text-navy-900 dark:text-zinc-100">
                        <flux:icon.key class="size-5" />
                    </span>
                    <div>
                        <h2 class="font-semibold text-navy-900 dark:text-zinc-100">Acceso y seguridad</h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Empresa, rol y contraseña inicial.</p>
                    </div>
                </div>

                <div class="mt-5 grid gap-5">
                    @if($actorIsSuperAdmin)
                        <flux:select wire:model.live="companyId" label="Empresa">
                            <option value="">Sin empresa (global)</option>
                            @foreach($companyOptions as $company)
                                <option value="{{ $company['id'] }}">{{ $company['name'] }}</option>
                            @endforeach
                        </flux:select>
                    @else
                        <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-800/40 p-4">
                            <p class="text-xs font-medium uppercase tracking-[0.12em] text-zinc-400">Empresa</p>
                            <p class="mt-1 font-medium text-navy-900 dark:text-zinc-100">{{ $companyOptions[0]['name'] ?? 'Sin empresa asignada' }}</p>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Un administrador de empresa solo puede crear usuarios dentro de su propia empresa.</p>
                        </div>
                    @endif

                    <div>
                        <p class="mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-300">Rol</p>
                        <div class="grid gap-2">
                            @foreach($roleOptions as $value => $label)
                                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-zinc-200 dark:border-zinc-800 p-3 transition hover:border-royal-300 dark:hover:border-royal-500 hover:bg-royal-50/30 dark:hover:bg-zinc-800/40">
                                    <flux:checkbox wire:model="roles" value="{{ $value }}" class="mt-0.5" />
                                    <span>
                                        <span class="block text-sm font-medium text-navy-900 dark:text-zinc-100">{{ $label }}</span>
                                        <span class="mt-0.5 block text-xs leading-5 text-zinc-500 dark:text-zinc-400">{{ $roleDescriptions[$value] ?? '' }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('roles') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:input
                            wire:model="password"
                            :label="$isEditing ? 'Nueva contraseña (opcional)' : 'Contraseña'"
                            type="password"
                            :required="! $isEditing"
                            autocomplete="new-password"
                            viewable
                        />

                        <flux:input
                            wire:model="password_confirmation"
                            label="Confirmar contraseña"
                            type="password"
                            :required="! $isEditing"
                            autocomplete="new-password"
                            viewable
                        />
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="surface-card p-5 sm:p-6">
                <div class="flex items-start gap-3 border-b border-zinc-100 dark:border-zinc-800 pb-4">
                    <span class="flex size-9 items-center justify-center rounded-xl bg-royal-50 dark:bg-royal-600/20 text-royal-700 dark:text-royal-300">
                        <flux:icon.building-office class="size-5" />
                    </span>
                    <div>
                        <h2 class="font-semibold text-navy-900 dark:text-zinc-100">Departamentos</h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Áreas que podrá atender.</p>
                    </div>
                </div>

                <div class="mt-5">
                    @if($companyId === '')
                        <p class="rounded-xl border border-dashed border-zinc-200 dark:border-zinc-800 p-4 text-sm text-zinc-500 dark:text-zinc-400">
                            Selecciona una empresa para asignar departamentos.
                        </p>
                    @elseif($departmentOptions === [])
                        <p class="rounded-xl border border-dashed border-zinc-200 dark:border-zinc-800 p-4 text-sm text-zinc-500 dark:text-zinc-400">
                            Esta empresa aún no tiene departamentos activos.
                        </p>
                    @else
                        <div class="grid gap-2">
                            @foreach($departmentOptions as $department)
                                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-zinc-200 dark:border-zinc-800 px-3 py-2.5 transition hover:border-royal-300 dark:hover:border-royal-500 hover:bg-royal-50/30 dark:hover:bg-zinc-800/40">
                                    <flux:checkbox wire:model="departmentIds" value="{{ $department['id'] }}" />
                                    <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $department['name'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif

                    @error('departmentIds') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="rounded-2xl border border-royal-100 dark:border-royal-800 bg-royal-50/60 dark:bg-royal-900/20 p-4 text-xs leading-5 text-royal-900">
                <p class="font-semibold">Principio de mínimo privilegio</p>
                <p class="mt-1 text-royal-800/80 dark:text-royal-200/80">
                    Asigna solo el rol y los departamentos necesarios. Los permisos se validan en el servidor.
                </p>
            </div>
        </div>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between lg:col-span-2">
            <flux:button :href="route('users.index')" variant="ghost" wire:navigate>
                Cancelar
            </flux:button>

            <flux:button variant="primary" type="submit" icon="check">
                {{ $isEditing ? 'Guardar cambios' : 'Crear usuario' }}
            </flux:button>
        </div>
    </form>
</div>
