<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-surface antialiased dark:bg-zinc-950">
        <div class="grid min-h-screen lg:grid-cols-[1.05fr_1fr]">
            <aside class="relative hidden overflow-hidden bg-navy-950 p-10 text-white lg:flex lg:flex-col">
                <div class="pointer-events-none absolute -left-24 -top-24 size-80 rounded-full bg-royal-600/30 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-32 -right-24 size-96 rounded-full bg-gold-400/10 blur-3xl"></div>

                <a href="{{ route('home') }}" class="relative z-10 flex items-center gap-3" wire:navigate>
                    <span class="flex size-11 items-center justify-center rounded-2xl bg-gold-400">
                        <x-app-logo-icon class="size-7 text-navy-950" />
                    </span>
                    <span class="text-lg font-semibold tracking-tight">{{ config('app.name', 'Soporte TI') }}</span>
                </a>

                <div class="relative z-10 my-auto max-w-xl py-16">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-gold-300">Continuidad operativa</p>
                    <h1 class="mt-5 text-4xl font-semibold leading-tight tracking-tight xl:text-5xl">
                        Cada equipo, cada cuenta y cada soporte bajo control.
                    </h1>
                    <p class="mt-5 max-w-lg text-base leading-7 text-royal-100">
                        Una plataforma clara para administrar servicios TI, proteger credenciales y conservar la trazabilidad de cada intervención.
                    </p>

                    <div class="mt-9 grid gap-3 text-sm text-royal-100">
                        <div class="flex items-start gap-3 rounded-xl border border-white/10 bg-white/5 p-3">
                            <span class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-lg bg-gold-400 text-navy-950">
                                <flux:icon.check class="size-3.5" />
                            </span>
                            <span>Información organizada por empresa, sede y responsable.</span>
                        </div>
                        <div class="flex items-start gap-3 rounded-xl border border-white/10 bg-white/5 p-3">
                            <span class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-lg bg-gold-400 text-navy-950">
                                <flux:icon.check class="size-3.5" />
                            </span>
                            <span>Accesos por rol y registro de actividad para auditoría.</span>
                        </div>
                        <div class="flex items-start gap-3 rounded-xl border border-white/10 bg-white/5 p-3">
                            <span class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-lg bg-gold-400 text-navy-950">
                                <flux:icon.check class="size-3.5" />
                            </span>
                            <span>Diseñado para trabajar con claridad en escritorio, tablet y móvil.</span>
                        </div>
                    </div>
                </div>

                <p class="relative z-10 text-xs text-royal-200">
                    © {{ date('Y') }} {{ config('app.name', 'Soporte TI') }}. Acceso restringido a personal autorizado.
                </p>
            </aside>

            <main class="flex min-h-screen items-center justify-center px-5 py-10 sm:px-8 lg:px-12">
                <div class="w-full max-w-md">
                    <a href="{{ route('home') }}" class="mb-8 flex items-center justify-center gap-3 lg:hidden" wire:navigate>
                        <span class="flex size-11 items-center justify-center rounded-2xl bg-gold-400">
                            <x-app-logo-icon class="size-7 text-navy-950" />
                        </span>
                        <span class="text-lg font-semibold tracking-tight text-navy-900 dark:text-zinc-100">{{ config('app.name', 'Soporte TI') }}</span>
                    </a>

                    <div class="rounded-3xl border border-zinc-200/80 bg-white p-6 shadow-[0_24px_70px_-35px_rgba(10,31,68,.35)] sm:p-8 dark:border-zinc-800 dark:bg-zinc-900 dark:shadow-none">
                        {{ $slot }}
                    </div>

                    <p class="mt-6 text-center text-xs leading-5 text-zinc-400 dark:text-zinc-500">
                        Si no tienes acceso, contacta al administrador de tu empresa.
                    </p>
                </div>
            </main>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
