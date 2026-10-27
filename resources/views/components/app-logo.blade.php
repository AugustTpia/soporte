@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand :name="config('app.name', 'Soporte TI')" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-9 items-center justify-center rounded-xl bg-gold-400 text-navy-950">
            <x-app-logo-icon class="size-6 text-navy-950" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand :name="config('app.name', 'Soporte TI')" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-9 items-center justify-center rounded-xl bg-gold-400 text-navy-950">
            <x-app-logo-icon class="size-6 text-navy-950" />
        </x-slot>
    </flux:brand>
@endif
