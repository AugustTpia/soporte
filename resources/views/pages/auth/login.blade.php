<x-layouts::auth :title="'Iniciar sesión'">
    <div class="flex flex-col gap-7">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-royal-600 dark:text-royal-300">Acceso seguro</p>
            <h1 class="mt-3 text-2xl font-semibold tracking-tight text-navy-900 sm:text-3xl dark:text-zinc-100">Inicia sesión</h1>
            <p class="mt-2 text-sm leading-6 text-zinc-500 dark:text-zinc-400">
                Usa tu cuenta corporativa para continuar.
            </p>
        </div>

        <x-auth-session-status class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-center text-sm text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
            @csrf

            <flux:input
                name="email"
                label="Correo electrónico"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="nombre@empresa.com"
            />

            <div class="relative">
                <flux:input
                    name="password"
                    label="Contraseña"
                    type="password"
                    required
                    autocomplete="current-password"
                    placeholder="Tu contraseña"
                    viewable
                />

                @if (Route::has('password.request'))
                    <flux:link class="absolute end-0 top-0 text-sm" :href="route('password.request')" wire:navigate>
                        ¿Olvidaste tu contraseña?
                    </flux:link>
                @endif
            </div>

            <flux:checkbox name="remember" label="Mantener mi sesión iniciada" :checked="old('remember')" />

            <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                Entrar
            </flux:button>
        </form>

        <x-passkey-verify
            label="Entrar con una llave de acceso"
            loading-label="Autenticando..."
            separator="O continúa con tu correo"
        />
    </div>
</x-layouts::auth>
