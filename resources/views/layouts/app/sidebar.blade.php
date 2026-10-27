<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-surface text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
        @php
            $tenantContext = app(\App\Shared\Application\Contracts\TenantContext::class);
            $activeCompanyId = $tenantContext->companyId();
            $activeCompany = $activeCompanyId ? app(\App\Companies\Domain\Repositories\CompanyRepository::class)->find($activeCompanyId) : null;
            $isSuperAdmin = auth()->user()->isSuperAdmin();
        @endphp

        <flux:sidebar sticky collapsible="mobile" class="app-sidebar border-e border-white/10">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden text-white" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group heading="Principal" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        Panel
                    </flux:sidebar.item>
                </flux:sidebar.group>

                @if($isSuperAdmin)
                    <flux:sidebar.group heading="Sistema" class="grid">
                        <flux:sidebar.item icon="building-office-2" :href="route('companies.index')" :current="request()->routeIs('companies.*')" wire:navigate>
                            Empresas
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endif

                @if($activeCompanyId !== null)
                    @can('viewAny', \App\Assets\Infrastructure\Persistence\Eloquent\Models\AssetModel::class)
                        <flux:sidebar.group heading="Operación" class="grid">
                            <flux:sidebar.item icon="computer-desktop" :href="route('assets.index')" :current="request()->routeIs('assets.*')" wire:navigate>
                                Activos
                            </flux:sidebar.item>
                        </flux:sidebar.group>
                    @endcan

                    @if(auth()->user()->can('departments.view') || auth()->user()->can('locations.view') || auth()->user()->can('users.view') || auth()->user()->can('audit.view'))
                        <flux:sidebar.group heading="Administración" class="grid">
                            @can('viewAny', \App\Departments\Infrastructure\Persistence\Eloquent\Models\DepartmentModel::class)
                                <flux:sidebar.item icon="building-office" :href="route('departments.index')" :current="request()->routeIs('departments.*')" wire:navigate>
                                    Departamentos
                                </flux:sidebar.item>
                            @endcan

                            @can('viewAny', \App\Locations\Infrastructure\Persistence\Eloquent\Models\LocationModel::class)
                                <flux:sidebar.item icon="map-pin" :href="route('locations.index')" :current="request()->routeIs('locations.*')" wire:navigate>
                                    Ubicaciones
                                </flux:sidebar.item>
                            @endcan

                            @can('viewAny', \App\Identity\Infrastructure\Persistence\Eloquent\User::class)
                                <flux:sidebar.item icon="users" :href="route('users.index')" :current="request()->routeIs('users.*')" wire:navigate>
                                    Usuarios
                                </flux:sidebar.item>
                            @endcan

                            @can('viewAny', \App\Auditing\Infrastructure\Persistence\Eloquent\Models\AuditLogModel::class)
                                <flux:sidebar.item icon="shield-check" :href="route('audit.index')" :current="request()->routeIs('audit.*')" wire:navigate>
                                    Auditoría
                                </flux:sidebar.item>
                            @endcan
                        </flux:sidebar.group>
                    @endif
                @elseif($isSuperAdmin)
                    @can('viewAny', \App\Auditing\Infrastructure\Persistence\Eloquent\Models\AuditLogModel::class)
                        <flux:sidebar.group heading="Auditoría" class="grid">
                            <flux:sidebar.item icon="shield-check" :href="route('audit.index')" :current="request()->routeIs('audit.*')" wire:navigate>
                                Auditoría global
                            </flux:sidebar.item>
                        </flux:sidebar.group>
                    @endcan
                @endif
            </flux:sidebar.nav>

            <flux:spacer />

            <div class="rounded-xl border border-white/10 bg-white/5 p-3">
                <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-royal-200">
                    {{ $isSuperAdmin ? 'Empresa activa' : 'Tu empresa' }}
                </p>
                <p class="mt-1 truncate text-sm font-medium text-white">
                    {{ $activeCompany?->name ?? ($isSuperAdmin ? 'Todas las empresas' : 'Sin empresa asignada') }}
                </p>
                @if($isSuperAdmin && $activeCompanyId === null)
                    <p class="mt-1 text-[10px] leading-4 text-royal-200/80">
                        Selecciona una empresa para administrar su equipo.
                    </p>
                @endif
            </div>

            <flux:sidebar.nav>
                <flux:sidebar.group heading="Cuenta" class="grid">
                    <flux:sidebar.item icon="cog-6-tooth" :href="route('profile.edit')" :current="request()->routeIs('profile.*')" wire:navigate>
                        Configuración
                    </flux:sidebar.item>

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:sidebar.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer" data-test="logout-button">
                            Cerrar sesión
                        </flux:sidebar.item>
                    </form>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <x-desktop-user-menu :name="auth()->user()->name" />
        </flux:sidebar>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
