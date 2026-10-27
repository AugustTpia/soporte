<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="mb-2 flex items-center gap-2 text-xs text-zinc-400">
                <a href="{{ route('departments.index') }}" class="hover:text-royal-600" wire:navigate>Departamentos</a>
                <span>/</span>
                <span>{{ $isEditing ? 'Editar' : 'Nuevo' }}</span>
            </div>
            <h1 class="page-title">{{ $isEditing ? 'Editar departamento' : 'Nuevo departamento' }}</h1>
            <p class="page-subtitle">
                {{ $companyName ? 'Área interna de '.$companyName.'.' : 'Organiza el alcance del equipo.' }}
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
            <div class="grid gap-5">
                <flux:input wire:model="name" label="Nombre del departamento" placeholder="Ej. Soporte técnico, Sistemas, Operaciones" required autofocus />

                <flux:textarea
                    wire:model="description"
                    label="Descripción"
                    placeholder="Describe brevemente el propósito de esta área."
                    rows="4"
                />

                <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-800/40 p-4">
                    <flux:checkbox wire:model="isActive" label="Departamento activo" description="Los departamentos inactivos se conservan para el historial, pero no se asignan a nuevos usuarios." />
                </div>
            </div>
        </div>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
            <flux:button :href="route('departments.index')" variant="ghost" wire:navigate>
                Cancelar
            </flux:button>

            <flux:button variant="primary" type="submit" icon="check">
                {{ $isEditing ? 'Guardar cambios' : 'Crear departamento' }}
            </flux:button>
        </div>
    </form>
</div>
