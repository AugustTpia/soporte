<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="mb-2 flex items-center gap-2 text-xs text-zinc-400">
                <a href="{{ route('projects.index') }}" class="hover:text-royal-600" wire:navigate>Proyectos</a>
                <span>/</span>
                <span>{{ $isEditing ? 'Editar' : 'Nuevo' }}</span>
            </div>
            <h1 class="page-title">{{ $isEditing ? 'Editar proyecto' : 'Nuevo proyecto' }}</h1>
            <p class="page-subtitle">
                {{ $isEditing ? 'Modifica los datos y el equipo del proyecto.' : 'Crea un proyecto para organizar tareas y equipos.' }}
            </p>
        </div>
    </div>

    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
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
                <h2 class="text-sm font-semibold text-navy-900 dark:text-zinc-100">Datos del proyecto</h2>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    Define el nombre, el estado y las fechas del proyecto.
                </p>
            </div>

            @if (!$isEditing && auth()->user()->isSuperAdmin() && $this->companyId === null)
                <div class="sm:col-span-2">
                    <flux:select wire:model="selectCompanyId" label="Empresa" required>
                        <option value="">Selecciona una empresa</option>
                        @foreach($companyOptions as $company)
                            <option value="{{ $company['id'] }}">{{ $company['name'] }}</option>
                        @endforeach
                    </flux:select>
                </div>
            @endif

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <flux:input
                        wire:model="name"
                        label="Nombre del proyecto"
                        placeholder="Ej. Implementación de red sucursal norte"
                        maxlength="160"
                        required
                        autofocus
                    />
                </div>

                <div class="sm:col-span-2">
                    <flux:textarea
                        wire:model="description"
                        label="Descripción"
                        placeholder="Alcance, objetivos y cualquier detalle útil para el equipo."
                        rows="4"
                    />
                </div>

                <flux:select wire:model="status" label="Estado" required>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="managerId" label="Responsable del proyecto" required>
                    <option value="">Selecciona un responsable operativo</option>
                    @foreach($userOptions as $user)
                        <option value="{{ $user['id'] }}">{{ $user['name'] }}</option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="startDate" label="Fecha de inicio" type="date" />
                <flux:input wire:model="dueDate" label="Fecha de vencimiento" type="date" />
            </div>
        </div>

        <div class="surface-card p-5 sm:p-6">
            <div class="mb-5">
                <h2 class="text-sm font-semibold text-navy-900 dark:text-zinc-100">Equipo del proyecto</h2>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    Selecciona los miembros que podrán ver y participar en el proyecto.
                </p>
            </div>

            <div class="sm:col-span-2">
                <select wire:model.live="memberIds" multiple size="6" class="w-full rounded-xl border border-zinc-200 p-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                    @foreach($userOptions as $user)
                        <option value="{{ $user['id'] }}">{{ $user['name'] }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Mantén presionada Ctrl o Cmd para seleccionar varios miembros.</p>
            </div>
        </div>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
            <flux:button :href="$isEditing ? route('projects.show', $projectId) : route('projects.index')" variant="ghost" wire:navigate>
                Cancelar
            </flux:button>

            <flux:button variant="primary" type="submit" icon="check">
                {{ $isEditing ? 'Guardar cambios' : 'Crear proyecto' }}
            </flux:button>
        </div>
    </form>
</div>
