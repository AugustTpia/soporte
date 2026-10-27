<div>
    @if(auth()->user()?->isSuperAdmin())
        <div class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-white px-3 py-1.5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon.building-office-2 class="size-4 shrink-0 text-royal-600 dark:text-royal-300" />

            <div class="flex min-w-0 flex-col gap-0.5">
                <span class="text-[10px] font-semibold uppercase tracking-[0.14em] text-zinc-400 dark:text-zinc-500">
                    Empresa activa
                </span>

                <flux:select
                    wire:model.live="selectedCompany"
                    size="sm"
                    class="min-w-48 border-0 bg-transparent"
                    aria-label="Seleccionar empresa activa"
                >
                    <option value="">Todas las empresas</option>
                    @foreach($companies as $company)
                        <option value="{{ $company['id'] }}">{{ $company['name'] }}</option>
                    @endforeach
                </flux:select>
            </div>
        </div>
    @endif
</div>
