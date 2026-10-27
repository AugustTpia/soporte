<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="min-h-screen bg-surface p-4 sm:p-6 lg:p-8 dark:bg-zinc-950">
        <div class="mx-auto w-full max-w-7xl">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

                    <span class="hidden text-xs font-medium uppercase tracking-[0.14em] text-zinc-400 sm:block dark:text-zinc-500">
                        Gestión de servicios TI
                    </span>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2">
                    @if(auth()->user()->isSuperAdmin())
                        <livewire:companies.company-switcher />
                    @else
                        <flux:badge color="zinc" size="sm" icon="building-office-2">
                            {{ auth()->user()->company?->name ?? 'Sin empresa' }}
                        </flux:badge>
                    @endif
                </div>
            </div>

            {{ $slot }}
        </div>
    </flux:main>
</x-layouts::app.sidebar>
