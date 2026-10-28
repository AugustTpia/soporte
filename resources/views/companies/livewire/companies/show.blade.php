<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
            <div class="mb-2 flex items-center gap-2 text-xs text-zinc-400">
                @if(auth()->user()->isSuperAdmin())
                    <a href="{{ route('companies.index') }}" class="hover:text-royal-600" wire:navigate>Empresas</a>
                    <span>/</span>
                @endif
                <span>Ficha del cliente</span>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <h1 class="page-title">{{ $company->name }}</h1>
                <flux:badge :color="$company->isActive() ? 'green' : 'zinc'" size="sm">
                    {{ $company->isActive() ? 'Activa' : 'Inactiva' }}
                </flux:badge>
            </div>

            <p class="page-subtitle">
                {{ $company->legalName ?: 'Sin razón social registrada' }}
                @if($company->industry)
                    · {{ $company->industry }}
                @endif
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            @if(auth()->user()->isSuperAdmin())
                <flux:button :href="route('companies.edit', $company->publicId)" variant="subtle" icon="pencil-square" wire:navigate>
                    Editar empresa
                </flux:button>
            @endif
            <flux:button :href="route('dashboard')" variant="ghost" wire:navigate>
                Volver
            </flux:button>
        </div>
    </div>

    <section class="surface-card p-5 sm:p-6">
        <div class="grid gap-5 text-sm sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-zinc-400">Correo</p>
                <p class="mt-1 break-all text-zinc-700 dark:text-zinc-300">{{ $company->email ?: '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-zinc-400">Teléfono</p>
                <p class="mt-1 text-zinc-700 dark:text-zinc-300">{{ $company->phone ?: '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-zinc-400">RFC</p>
                <p class="mt-1 text-zinc-700 dark:text-zinc-300">{{ $company->taxId ?: '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-zinc-400">Sitio web</p>
                <p class="mt-1 break-all text-zinc-700 dark:text-zinc-300">{{ $company->website ?: '—' }}</p>
            </div>
        </div>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @if(array_key_exists('contacts', $overview->counts))
            <div class="surface-card p-5">
                <span class="flex size-10 items-center justify-center rounded-xl bg-royal-50 text-royal-700 dark:bg-royal-600/20 dark:text-royal-300">
                    <flux:icon.user-group class="size-5" />
                </span>
                <p class="mt-4 text-3xl font-semibold tracking-tight text-navy-900 dark:text-zinc-100">{{ $overview->counts['contacts'] }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Contactos activos</p>
            </div>
        @endif

        @if(array_key_exists('locations', $overview->counts))
            <a href="{{ route('locations.index') }}" wire:navigate class="surface-card p-5 transition hover:border-royal-200 dark:hover:border-royal-500/40">
                <span class="flex size-10 items-center justify-center rounded-xl bg-sky-50 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300">
                    <flux:icon.map-pin class="size-5" />
                </span>
                <p class="mt-4 text-3xl font-semibold tracking-tight text-navy-900 dark:text-zinc-100">{{ $overview->counts['locations'] }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Sedes activas</p>
            </a>
        @endif

        @if(array_key_exists('assets', $overview->counts))
            <a href="{{ route('assets.index') }}" wire:navigate class="surface-card p-5 transition hover:border-royal-200 dark:hover:border-royal-500/40">
                <span class="flex size-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                    <flux:icon.computer-desktop class="size-5" />
                </span>
                <p class="mt-4 text-3xl font-semibold tracking-tight text-navy-900 dark:text-zinc-100">{{ $overview->counts['assets'] }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Activos operativos</p>
            </a>
        @endif

        @if(array_key_exists('accounts', $overview->counts))
            <a href="{{ route('accounts.index') }}" wire:navigate class="surface-card p-5 transition hover:border-royal-200 dark:hover:border-royal-500/40">
                <span class="flex size-10 items-center justify-center rounded-xl bg-violet-50 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300">
                    <flux:icon.key class="size-5" />
                </span>
                <p class="mt-4 text-3xl font-semibold tracking-tight text-navy-900 dark:text-zinc-100">{{ $overview->counts['accounts'] }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Cuentas activas</p>
            </a>
        @endif

        @if(array_key_exists('tickets', $overview->counts))
            <a href="{{ route('tickets.index') }}" wire:navigate class="surface-card p-5 transition hover:border-royal-200 dark:hover:border-royal-500/40">
                <span class="flex size-10 items-center justify-center rounded-xl bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                    <flux:icon.ticket class="size-5" />
                </span>
                <p class="mt-4 text-3xl font-semibold tracking-tight text-navy-900 dark:text-zinc-100">{{ $overview->counts['tickets'] }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Tickets abiertos</p>
            </a>
        @endif

        @if(array_key_exists('maintenance', $overview->counts))
            <a href="{{ route('maintenance.index') }}" wire:navigate class="surface-card p-5 transition hover:border-royal-200 dark:hover:border-royal-500/40">
                <span class="flex size-10 items-center justify-center rounded-xl bg-rose-50 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300">
                    <flux:icon.wrench-screwdriver class="size-5" />
                </span>
                <p class="mt-4 text-3xl font-semibold tracking-tight text-navy-900 dark:text-zinc-100">{{ $overview->counts['maintenance'] }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Mantenimientos próximos</p>
            </a>
        @endif

        @if(array_key_exists('cancellations', $overview->counts))
            <a href="{{ route('bus-ticket-cancellations.index') }}" wire:navigate class="surface-card p-5 transition hover:border-royal-200 dark:hover:border-royal-500/40">
                <span class="flex size-10 items-center justify-center rounded-xl bg-orange-50 text-orange-700 dark:bg-orange-500/15 dark:text-orange-300">
                    <flux:icon.receipt-refund class="size-5" />
                </span>
                <p class="mt-4 text-3xl font-semibold tracking-tight text-navy-900 dark:text-zinc-100">{{ $overview->counts['cancellations'] }}</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Cancelaciones 30 días</p>
            </a>
        @endif
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.45fr)_minmax(320px,0.75fr)]">
        <div class="space-y-6">
            @if($canViewContacts)
                @livewire(\App\Contacts\Presentation\Livewire\CompanyContacts::class, [
                    'companyId' => $company->id,
                ], key('company-contacts-'.$company->id))
            @endif

            @if($canViewTickets && $overview->recentTickets !== [])
                <section class="surface-card overflow-hidden">
                    <div class="flex items-center justify-between border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
                        <div>
                            <h2 class="font-semibold text-navy-900 dark:text-zinc-100">Tickets recientes</h2>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Últimas solicitudes de soporte del cliente.</p>
                        </div>
                        <flux:button size="sm" variant="subtle" :href="route('tickets.index')" wire:navigate>Ver todos</flux:button>
                    </div>

                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach($overview->recentTickets as $ticket)
                            <a
                                href="{{ route('tickets.show', $ticket['id']) }}"
                                wire:navigate
                                class="flex items-start justify-between gap-4 px-5 py-4 transition hover:bg-royal-50/40 dark:hover:bg-zinc-800/60"
                            >
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-navy-900 dark:text-zinc-100">{{ $ticket['subject'] }}</p>
                                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $ticket['folio'] }} · {{ $ticket['opened_at'] }}</p>
                                </div>
                                <flux:badge :color="\App\Support\Domain\Enums\TicketStatus::from($ticket['status'])->color()" size="sm">
                                    {{ \App\Support\Domain\Enums\TicketStatus::from($ticket['status'])->label() }}
                                </flux:badge>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            @if($canViewMaintenance && $overview->recentMaintenance !== [])
                <section class="surface-card overflow-hidden">
                    <div class="flex items-center justify-between border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
                        <div>
                            <h2 class="font-semibold text-navy-900 dark:text-zinc-100">Mantenimientos recientes</h2>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Últimas ejecuciones registradas.</p>
                        </div>
                        <flux:button size="sm" variant="subtle" :href="route('maintenance.executions.index')" wire:navigate>Ver todos</flux:button>
                    </div>

                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach($overview->recentMaintenance as $execution)
                            <a
                                href="{{ route('maintenance.executions.show', $execution['id']) }}"
                                wire:navigate
                                class="flex items-start justify-between gap-4 px-5 py-4 transition hover:bg-royal-50/40 dark:hover:bg-zinc-800/60"
                            >
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-navy-900 dark:text-zinc-100">{{ $execution['title'] }}</p>
                                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $execution['scheduled_at'] }}</p>
                                </div>
                                <flux:badge :color="\App\Maintenance\Domain\Enums\MaintenanceExecutionStatus::from($execution['status'])->color()" size="sm">
                                    {{ \App\Maintenance\Domain\Enums\MaintenanceExecutionStatus::from($execution['status'])->label() }}
                                </flux:badge>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            @if($canViewCancellations && $overview->recentCancellations !== [])
                <section class="surface-card overflow-hidden">
                    <div class="flex items-center justify-between border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
                        <div>
                            <h2 class="font-semibold text-navy-900 dark:text-zinc-100">Cancelaciones recientes</h2>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Últimas solicitudes de cancelación de boletos.</p>
                        </div>
                        <flux:button size="sm" variant="subtle" :href="route('bus-ticket-cancellations.index')" wire:navigate>Ver todas</flux:button>
                    </div>

                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach($overview->recentCancellations as $cancellation)
                            <a
                                href="{{ route('bus-ticket-cancellations.show', $cancellation['id']) }}"
                                wire:navigate
                                class="flex items-start justify-between gap-4 px-5 py-4 transition hover:bg-royal-50/40 dark:hover:bg-zinc-800/60"
                            >
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-navy-900 dark:text-zinc-100">Boleto {{ $cancellation['ticket_number'] }}</p>
                                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $cancellation['requested_at'] }}</p>
                                </div>
                                <flux:badge :color="\App\BusTicketCancellations\Domain\Enums\BusTicketCancellationResult::from($cancellation['result'])->color()" size="sm">
                                    {{ \App\BusTicketCancellations\Domain\Enums\BusTicketCancellationResult::from($cancellation['result'])->label() }}
                                </flux:badge>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        <div class="space-y-6">
            @if($canViewActivity && $overview->recentActivity !== [])
                <section class="surface-card p-5">
                    <h2 class="font-semibold text-navy-900 dark:text-zinc-100">Actividad reciente</h2>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Últimos eventos auditados de la empresa.</p>

                    <div class="mt-4 space-y-4">
                        @foreach($overview->recentActivity as $activity)
                            <div class="flex gap-3">
                                <span class="mt-1 flex size-2 shrink-0 rounded-full bg-royal-500"></span>
                                <div class="min-w-0">
                                    <p class="text-sm leading-5 text-zinc-700 dark:text-zinc-300">{{ $activity['description'] }}</p>
                                    <p class="mt-0.5 text-xs text-zinc-400">
                                        {{ $activity['actor'] ?: 'Sistema' }} · {{ $activity['created_at'] }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="surface-card p-5">
                <h2 class="font-semibold text-navy-900 dark:text-zinc-100">Accesos rápidos</h2>
                <div class="mt-4 grid gap-2">
                    @if(auth()->user()->can('locations.view'))
                        <a href="{{ route('locations.index') }}" wire:navigate class="rounded-xl border border-zinc-200 px-3 py-2 text-sm text-zinc-700 transition hover:border-royal-200 hover:text-royal-700 dark:border-zinc-800 dark:text-zinc-300 dark:hover:border-royal-500/40 dark:hover:text-royal-300">
                            Ubicaciones
                        </a>
                    @endif
                    @if(auth()->user()->can('assets.view'))
                        <a href="{{ route('assets.index') }}" wire:navigate class="rounded-xl border border-zinc-200 px-3 py-2 text-sm text-zinc-700 transition hover:border-royal-200 hover:text-royal-700 dark:border-zinc-800 dark:text-zinc-300 dark:hover:border-royal-500/40 dark:hover:text-royal-300">
                            Activos
                        </a>
                    @endif
                    @if(auth()->user()->can('accounts.view'))
                        <a href="{{ route('accounts.index') }}" wire:navigate class="rounded-xl border border-zinc-200 px-3 py-2 text-sm text-zinc-700 transition hover:border-royal-200 hover:text-royal-700 dark:border-zinc-800 dark:text-zinc-300 dark:hover:border-royal-500/40 dark:hover:text-royal-300">
                            Cuentas de servicio
                        </a>
                    @endif
                    @if(auth()->user()->can('tickets.view'))
                        <a href="{{ route('tickets.index') }}" wire:navigate class="rounded-xl border border-zinc-200 px-3 py-2 text-sm text-zinc-700 transition hover:border-royal-200 hover:text-royal-700 dark:border-zinc-800 dark:text-zinc-300 dark:hover:border-royal-500/40 dark:hover:text-royal-300">
                            Tickets
                        </a>
                    @endif
                    @if(auth()->user()->can('maintenance.view'))
                        <a href="{{ route('maintenance.index') }}" wire:navigate class="rounded-xl border border-zinc-200 px-3 py-2 text-sm text-zinc-700 transition hover:border-royal-200 hover:text-royal-700 dark:border-zinc-800 dark:text-zinc-300 dark:hover:border-royal-500/40 dark:hover:text-royal-300">
                            Mantenimientos
                        </a>
                    @endif
                </div>
            </section>
        </div>
    </div>
</div>
