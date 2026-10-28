<div class="space-y-6">
    @if($company)
        <section class="relative overflow-hidden rounded-3xl bg-navy-950 p-6 text-white shadow-[0_24px_70px_-35px_rgba(10,31,68,.55)] sm:p-8">
            <div class="pointer-events-none absolute -right-20 -top-24 size-72 rounded-full bg-royal-600/40 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-24 left-1/3 size-64 rounded-full bg-gold-400/10 blur-3xl"></div>

            <div class="relative z-10 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-2xl">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-gold-300">Empresa activa</p>
                        <flux:badge :color="$company->isActive() ? 'green' : 'zinc'" size="sm">
                            {{ $company->isActive() ? 'Operativa' : 'Inactiva' }}
                        </flux:badge>
                    </div>
                    <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">{{ $company->name }}</h1>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-royal-100">
                        {{ $company->legalName ?: 'Administra los usuarios, departamentos y actividad de esta empresa.' }}
                    </p>
                    <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-royal-200">
                        <span class="inline-flex items-center gap-1.5">
                            <flux:icon.globe-alt class="size-3.5" />
                            {{ $company->timezone }}
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <flux:icon.identification class="size-3.5" />
                            {{ $company->publicId }}
                        </span>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    @if(auth()->user()->isSuperAdmin())
                        <flux:button variant="filled" :href="route('companies.edit', $company->publicId)" wire:navigate icon="pencil-square">
                            Editar empresa
                        </flux:button>
                    @endif

                    @can('viewAny', \App\Identity\Infrastructure\Persistence\Eloquent\User::class)
                        <flux:button variant="ghost" :href="route('users.index')" wire:navigate icon="users" class="!text-white hover:!bg-white/10">
                            Usuarios
                        </flux:button>
                    @endcan
                </div>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 {{ $canViewAudit ? 'xl:grid-cols-4' : 'xl:grid-cols-3' }}">
            <div class="surface-card p-5">
                <div class="flex items-start justify-between">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-royal-50 text-royal-700 dark:bg-royal-600/20 dark:text-royal-300">
                        <flux:icon.users class="size-5" />
                    </span>
                    <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">{{ $stats['users_active'] }} activos</span>
                </div>
                <p class="mt-4 text-3xl font-semibold tracking-tight text-navy-900 dark:text-zinc-100">{{ $stats['users_total'] }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Usuarios registrados</p>
            </div>

            <div class="surface-card p-5">
                <div class="flex items-start justify-between">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-gold-400/20 text-navy-900 dark:bg-gold-400/10 dark:text-gold-300">
                        <flux:icon.building-office class="size-5" />
                    </span>
                    <span class="text-xs font-medium text-zinc-400">{{ $stats['departments_active'] }} activos</span>
                </div>
                <p class="mt-4 text-3xl font-semibold tracking-tight text-navy-900 dark:text-zinc-100">{{ $stats['departments_total'] }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Departamentos</p>
            </div>

            @if($canViewAudit)
                <div class="surface-card p-5">
                    <div class="flex items-start justify-between">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-royal-50 text-royal-700 dark:bg-royal-600/20 dark:text-royal-300">
                            <flux:icon.shield-check class="size-5" />
                        </span>
                    </div>
                    <p class="mt-4 text-3xl font-semibold tracking-tight text-navy-900 dark:text-zinc-100">{{ $stats['audit_total'] }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Eventos auditados</p>
                </div>
            @endif

            <div class="surface-card p-5">
                <div class="flex items-start justify-between">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                        <flux:icon.user class="size-5" />
                    </span>
                </div>
                <p class="mt-4 text-3xl font-semibold tracking-tight text-navy-900 dark:text-zinc-100">
                    {{ \App\Identity\Domain\Enums\RoleName::tryFrom(auth()->user()->getRoleNames()->first() ?? '')?->label() ?? 'Usuario' }}
                </p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Tu rol de acceso</p>
            </div>
        </section>

        @if($maintenanceStats !== [])
            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <a href="{{ route('maintenance.index') }}" class="surface-card p-5 transition hover:border-royal-300 hover:bg-royal-50/30 dark:hover:bg-zinc-800/60" wire:navigate>
                    <div class="flex items-start justify-between">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-royal-50 text-royal-700 dark:bg-royal-600/20 dark:text-royal-300">
                            <flux:icon.wrench-screwdriver class="size-5" />
                        </span>
                    </div>
                    <p class="mt-4 text-3xl font-semibold tracking-tight text-navy-900 dark:text-zinc-100">{{ $maintenanceStats['active_plans'] ?? 0 }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Planes activos</p>
                </a>

                <a href="{{ route('maintenance.index', ['overdue' => 1]) }}" class="surface-card p-5 transition hover:border-rose-300 hover:bg-rose-50/30 dark:hover:bg-zinc-800/60" wire:navigate>
                    <div class="flex items-start justify-between">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-rose-50 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300">
                            <flux:icon.exclamation-triangle class="size-5" />
                        </span>
                    </div>
                    <p class="mt-4 text-3xl font-semibold tracking-tight text-navy-900 dark:text-zinc-100">{{ $maintenanceStats['overdue'] ?? 0 }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Planes vencidos</p>
                </a>

                <a href="{{ route('maintenance.executions.index', ['status' => 'scheduled']) }}" class="surface-card p-5 transition hover:border-amber-300 hover:bg-amber-50/30 dark:hover:bg-zinc-800/60" wire:navigate>
                    <div class="flex items-start justify-between">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                            <flux:icon.calendar-days class="size-5" />
                        </span>
                    </div>
                    <p class="mt-4 text-3xl font-semibold tracking-tight text-navy-900 dark:text-zinc-100">{{ $maintenanceStats['scheduled'] ?? 0 }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Mantenimientos programados</p>
                </a>

                <a href="{{ route('maintenance.executions.index', ['status' => 'in_progress']) }}" class="surface-card p-5 transition hover:border-emerald-300 hover:bg-emerald-50/30 dark:hover:bg-zinc-800/60" wire:navigate>
                    <div class="flex items-start justify-between">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                            <flux:icon.play class="size-5" />
                        </span>
                    </div>
                    <p class="mt-4 text-3xl font-semibold tracking-tight text-navy-900 dark:text-zinc-100">{{ $maintenanceStats['in_progress'] ?? 0 }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Mantenimientos en progreso</p>
                </a>
            </section>
        @endif

        @if($ticketStats !== [])
            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <a href="{{ route('tickets.index', ['scope' => 'unresolved']) }}" class="surface-card p-5 transition hover:border-royal-300 hover:bg-royal-50/30 dark:hover:bg-zinc-800/60" wire:navigate>
                    <div class="flex items-start justify-between">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-royal-50 text-royal-700 dark:bg-royal-600/20 dark:text-royal-300">
                            <flux:icon.ticket class="size-5" />
                        </span>
                    </div>
                    <p class="mt-4 text-3xl font-semibold tracking-tight text-navy-900 dark:text-zinc-100">{{ $ticketStats['unresolved'] ?? 0 }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Tickets sin resolver</p>
                </a>

                <a href="{{ route('tickets.index', ['scope' => 'unassigned']) }}" class="surface-card p-5 transition hover:border-amber-300 hover:bg-amber-50/30 dark:hover:bg-zinc-800/60" wire:navigate>
                    <div class="flex items-start justify-between">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                            <flux:icon.user-minus class="size-5" />
                        </span>
                    </div>
                    <p class="mt-4 text-3xl font-semibold tracking-tight text-navy-900 dark:text-zinc-100">{{ $ticketStats['unassigned'] ?? 0 }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Sin asignar</p>
                </a>

                <a href="{{ route('tickets.index', ['scope' => 'mine']) }}" class="surface-card p-5 transition hover:border-emerald-300 hover:bg-emerald-50/30 dark:hover:bg-zinc-800/60" wire:navigate>
                    <div class="flex items-start justify-between">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                            <flux:icon.user class="size-5" />
                        </span>
                    </div>
                    <p class="mt-4 text-3xl font-semibold tracking-tight text-navy-900 dark:text-zinc-100">{{ $ticketStats['mine'] ?? 0 }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Asignados a mí</p>
                </a>

                <a href="{{ route('tickets.index', ['scope' => 'unresolved', 'priority' => 'urgent']) }}" class="surface-card p-5 transition hover:border-rose-300 hover:bg-rose-50/30 dark:hover:bg-zinc-800/60" wire:navigate>
                    <div class="flex items-start justify-between">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-rose-50 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300">
                            <flux:icon.exclamation-triangle class="size-5" />
                        </span>
                    </div>
                    <p class="mt-4 text-3xl font-semibold tracking-tight text-navy-900 dark:text-zinc-100">{{ $ticketStats['urgent'] ?? 0 }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Urgentes sin resolver</p>
                </a>
            </section>
        @endif

        <div class="grid gap-6 {{ $canViewAudit ? 'xl:grid-cols-[1.2fr_1fr]' : '' }}">
            @if($canViewAudit)
                <section class="surface-card overflow-hidden">
                    <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 px-5 py-4">
                        <div>
                            <h2 class="font-semibold text-navy-900 dark:text-zinc-100">Actividad reciente</h2>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Últimos cambios administrativos de la empresa.</p>
                        </div>
                        @can('viewAny', \App\Auditing\Infrastructure\Persistence\Eloquent\Models\AuditLogModel::class)
                            <flux:button size="sm" variant="subtle" :href="route('audit.index')" wire:navigate>
                                Ver todo
                            </flux:button>
                        @endcan
                    </div>

                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse($recentAudit as $log)
                            <div class="flex gap-3 px-5 py-4">
                                <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg bg-royal-50 text-royal-700 dark:bg-royal-600/20 dark:text-royal-300">
                                    <flux:icon.clock class="size-4" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $log->description }}</p>
                                    <p class="mt-1 text-xs text-zinc-400">
                                        {{ $log->actorName ?? 'Sistema' }} · {{ $log->createdAt->format('d/m/Y H:i') }}
                                    </p>
                                </div>
                            </div>
                        @empty
                            <div class="px-5 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                Aún no hay actividad registrada.
                            </div>
                        @endforelse
                    </div>
                </section>
            @endif

            <div class="space-y-6">
                <section class="surface-card p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="font-semibold text-navy-900 dark:text-zinc-100">Accesos rápidos</h2>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Áreas disponibles según tu rol.</p>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-2">
                        @can('viewAny', \App\Support\Infrastructure\Persistence\Eloquent\Models\SupportTicketModel::class)
                            <a href="{{ route('tickets.index') }}" class="flex items-center gap-3 rounded-xl border border-zinc-200 dark:border-zinc-800 p-3 transition hover:border-royal-300 hover:bg-royal-50/40 dark:hover:bg-zinc-800/60" wire:navigate>
                                <span class="flex size-9 items-center justify-center rounded-lg bg-royal-50 text-royal-700 dark:bg-royal-600/20 dark:text-royal-300">
                                    <flux:icon.ticket class="size-4" />
                                </span>
                                <span class="flex-1">
                                    <span class="block text-sm font-medium text-navy-900 dark:text-zinc-100">Tickets</span>
                                    <span class="block text-xs text-zinc-500 dark:text-zinc-400">Solicitudes de soporte y seguimiento</span>
                                </span>
                                <flux:icon.chevron-right class="size-4 text-zinc-400" />
                            </a>
                        @endcan

                        @can('viewAny', \App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenancePlanModel::class)
                            <a href="{{ route('maintenance.index') }}" class="flex items-center gap-3 rounded-xl border border-zinc-200 dark:border-zinc-800 p-3 transition hover:border-royal-300 hover:bg-royal-50/40 dark:hover:bg-zinc-800/60" wire:navigate>
                                <span class="flex size-9 items-center justify-center rounded-lg bg-royal-50 text-royal-700 dark:bg-royal-600/20 dark:text-royal-300">
                                    <flux:icon.wrench-screwdriver class="size-4" />
                                </span>
                                <span class="flex-1">
                                    <span class="block text-sm font-medium text-navy-900 dark:text-zinc-100">Mantenimientos</span>
                                    <span class="block text-xs text-zinc-500 dark:text-zinc-400">Planes preventivos y ejecuciones</span>
                                </span>
                                <flux:icon.chevron-right class="size-4 text-zinc-400" />
                            </a>
                        @endcan

                        @if(auth()->user()->can('tickets.view') || auth()->user()->can('maintenance.view') || ($company->hasFeature(\App\Companies\Domain\Enums\CompanyFeature::BusTicketCancellations) && auth()->user()->can('bus_ticket_cancellations.view')))
                            <a href="{{ route('reports.index') }}" class="flex items-center gap-3 rounded-xl border border-zinc-200 dark:border-zinc-800 p-3 transition hover:border-royal-300 hover:bg-royal-50/40 dark:hover:bg-zinc-800/60" wire:navigate>
                                <span class="flex size-9 items-center justify-center rounded-lg bg-royal-50 text-royal-700 dark:bg-royal-600/20 dark:text-royal-300">
                                    <flux:icon.chart-bar class="size-4" />
                                </span>
                                <span class="flex-1">
                                    <span class="block text-sm font-medium text-navy-900 dark:text-zinc-100">Reportes</span>
                                    <span class="block text-xs text-zinc-500 dark:text-zinc-400">Tickets, mantenimientos y cancelaciones</span>
                                </span>
                                <flux:icon.chevron-right class="size-4 text-zinc-400" />
                            </a>
                        @endif

                        @can('viewAny', \App\Departments\Infrastructure\Persistence\Eloquent\Models\DepartmentModel::class)
                            <a href="{{ route('departments.index') }}" class="flex items-center gap-3 rounded-xl border border-zinc-200 dark:border-zinc-800 p-3 transition hover:border-royal-300 hover:bg-royal-50/40 dark:hover:bg-zinc-800/60" wire:navigate>
                                <span class="flex size-9 items-center justify-center rounded-lg bg-gold-400/20 text-navy-900 dark:bg-gold-400/10 dark:text-gold-300">
                                    <flux:icon.building-office class="size-4" />
                                </span>
                                <span class="flex-1">
                                    <span class="block text-sm font-medium text-navy-900 dark:text-zinc-100">Departamentos</span>
                                    <span class="block text-xs text-zinc-500 dark:text-zinc-400">Organización y alcance del equipo</span>
                                </span>
                                <flux:icon.chevron-right class="size-4 text-zinc-400" />
                            </a>
                        @endcan

                        @can('viewAny', \App\Identity\Infrastructure\Persistence\Eloquent\User::class)
                            <a href="{{ route('users.index') }}" class="flex items-center gap-3 rounded-xl border border-zinc-200 dark:border-zinc-800 p-3 transition hover:border-royal-300 hover:bg-royal-50/40 dark:hover:bg-zinc-800/60" wire:navigate>
                                <span class="flex size-9 items-center justify-center rounded-lg bg-royal-50 text-royal-700 dark:bg-royal-600/20 dark:text-royal-300">
                                    <flux:icon.users class="size-4" />
                                </span>
                                <span class="flex-1">
                                    <span class="block text-sm font-medium text-navy-900 dark:text-zinc-100">Usuarios</span>
                                    <span class="block text-xs text-zinc-500 dark:text-zinc-400">Accesos, roles y estados</span>
                                </span>
                                <flux:icon.chevron-right class="size-4 text-zinc-400" />
                            </a>
                        @endcan

                        @can('viewAny', \App\Auditing\Infrastructure\Persistence\Eloquent\Models\AuditLogModel::class)
                            <a href="{{ route('audit.index') }}" class="flex items-center gap-3 rounded-xl border border-zinc-200 dark:border-zinc-800 p-3 transition hover:border-royal-300 hover:bg-royal-50/40 dark:hover:bg-zinc-800/60" wire:navigate>
                                <span class="flex size-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                                    <flux:icon.shield-check class="size-4" />
                                </span>
                                <span class="flex-1">
                                    <span class="block text-sm font-medium text-navy-900 dark:text-zinc-100">Auditoría</span>
                                    <span class="block text-xs text-zinc-500 dark:text-zinc-400">Trazabilidad de la actividad</span>
                                </span>
                                <flux:icon.chevron-right class="size-4 text-zinc-400" />
                            </a>
                        @endcan
                    </div>
                </section>

                <section class="surface-card p-5">
                    <h2 class="font-semibold text-navy-900 dark:text-zinc-100">Tu acceso</h2>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Información de tu cuenta en esta empresa.</p>

                    <div class="mt-4 space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-zinc-500 dark:text-zinc-400">Rol</span>
                            <span class="font-medium text-navy-900 dark:text-zinc-100">
                                {{ \App\Identity\Domain\Enums\RoleName::tryFrom(auth()->user()->getRoleNames()->first() ?? '')?->label() ?? 'Sin rol' }}
                            </span>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <span class="text-zinc-500 dark:text-zinc-400">Departamentos</span>
                            <span class="text-right font-medium text-navy-900 dark:text-zinc-100">
                                {{ auth()->user()->departments->pluck('name')->join(', ') ?: 'Toda la empresa' }}
                            </span>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    @else
        <section class="relative overflow-hidden rounded-3xl bg-navy-950 p-6 text-white shadow-[0_24px_70px_-35px_rgba(10,31,68,.55)] sm:p-8">
            <div class="pointer-events-none absolute -right-20 -top-24 size-72 rounded-full bg-royal-600/40 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-24 left-1/3 size-64 rounded-full bg-gold-400/10 blur-3xl"></div>

            <div class="relative z-10 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-2xl">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-gold-300">Vista global</p>
                    <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">Todas las empresas</h1>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-royal-100">
                        Supervisa la operación completa y selecciona una empresa para administrar su equipo.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <flux:button variant="filled" :href="route('companies.create')" wire:navigate icon="plus">
                        Nueva empresa
                    </flux:button>
                    <flux:button variant="ghost" :href="route('companies.index')" wire:navigate icon="building-office-2" class="!text-white hover:!bg-white/10">
                        Ver empresas
                    </flux:button>
                </div>
            </div>
        </section>

        <div class="flex items-start gap-3 rounded-2xl border border-royal-200 bg-royal-50 p-4 text-sm text-royal-900 dark:border-royal-800 dark:bg-royal-900/20 dark:text-royal-100">
            <flux:icon.information-circle class="mt-0.5 size-5 shrink-0 text-royal-600 dark:text-royal-300" />
            <div>
                <p class="font-semibold">Selecciona una empresa para administrar su equipo</p>
                <p class="mt-1 text-royal-800/80 dark:text-royal-200/80">
                    Usa el selector <span class="font-semibold">Empresa activa</span> de la barra superior. Al elegir una empresa aparecerán Usuarios, Departamentos y Auditoría.
                </p>
            </div>
        </div>

        <section class="grid gap-4 sm:grid-cols-3">
            <div class="surface-card p-5">
                <span class="flex size-10 items-center justify-center rounded-xl bg-royal-50 text-royal-700 dark:bg-royal-600/20 dark:text-royal-300">
                    <flux:icon.building-office-2 class="size-5" />
                </span>
                <p class="mt-4 text-3xl font-semibold tracking-tight text-navy-900 dark:text-zinc-100">{{ $stats['companies_total'] }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Empresas registradas</p>
            </div>

            <div class="surface-card p-5">
                <span class="flex size-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                    <flux:icon.check-circle class="size-5" />
                </span>
                <p class="mt-4 text-3xl font-semibold tracking-tight text-navy-900 dark:text-zinc-100">{{ $stats['companies_active'] }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Empresas activas</p>
            </div>

            <div class="surface-card p-5">
                <span class="flex size-10 items-center justify-center rounded-xl bg-gold-400/20 text-navy-900 dark:bg-gold-400/10 dark:text-gold-300">
                    <flux:icon.users class="size-5" />
                </span>
                <p class="mt-4 text-3xl font-semibold tracking-tight text-navy-900 dark:text-zinc-100">{{ $stats['users_total'] }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Usuarios en la plataforma</p>
            </div>
        </section>

        <div class="grid gap-6 {{ $canViewAudit ? 'xl:grid-cols-[1.2fr_1fr]' : '' }}">
            <section class="surface-card overflow-hidden">
                <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 px-5 py-4">
                    <div>
                        <h2 class="font-semibold text-navy-900 dark:text-zinc-100">Empresas recientes</h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Últimos registros de la plataforma.</p>
                    </div>
                    <flux:button size="sm" variant="subtle" :href="route('companies.index')" wire:navigate>
                        Ver todas
                    </flux:button>
                </div>

                <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse($recentCompanies as $recentCompany)
                        <a href="{{ route('companies.edit', $recentCompany->publicId) }}" class="flex items-center gap-3 px-5 py-4 transition hover:bg-royal-50/40 dark:hover:bg-zinc-800/60" wire:navigate>
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-royal-50 text-royal-700 dark:bg-royal-600/20 dark:text-royal-300">
                                <flux:icon.building-office-2 class="size-5" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium text-navy-900 dark:text-zinc-100">{{ $recentCompany->name }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $recentCompany->usersCount }} usuarios · {{ $recentCompany->departmentsCount }} áreas
                                </p>
                            </div>
                            <flux:badge :color="$recentCompany->isActive() ? 'green' : 'zinc'" size="sm">
                                {{ $recentCompany->isActive() ? 'Activa' : 'Inactiva' }}
                            </flux:badge>
                        </a>
                    @empty
                        <div class="px-5 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            Aún no hay empresas registradas.
                        </div>
                    @endforelse
                </div>
            </section>

            @if($canViewAudit)
                <section class="surface-card overflow-hidden">
                    <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 px-5 py-4">
                        <div>
                            <h2 class="font-semibold text-navy-900 dark:text-zinc-100">Actividad global</h2>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Últimos eventos auditados.</p>
                        </div>
                        @can('viewAny', \App\Auditing\Infrastructure\Persistence\Eloquent\Models\AuditLogModel::class)
                            <flux:button size="sm" variant="subtle" :href="route('audit.index')" wire:navigate>
                                Ver todo
                            </flux:button>
                        @endcan
                    </div>

                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse($recentAudit as $log)
                            <div class="flex gap-3 px-5 py-4">
                                <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg bg-royal-50 text-royal-700 dark:bg-royal-600/20 dark:text-royal-300">
                                    <flux:icon.clock class="size-4" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $log->description }}</p>
                                    <p class="mt-1 text-xs text-zinc-400">
                                        {{ $log->companyName ?? 'Global' }} · {{ $log->createdAt->format('d/m/Y H:i') }}
                                    </p>
                                </div>
                            </div>
                        @empty
                            <div class="px-5 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                Aún no hay actividad registrada.
                            </div>
                        @endforelse
                    </div>
                </section>
            @endif
        </div>
    @endif
</div>
