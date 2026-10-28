<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="mb-2 flex items-center gap-2 text-xs text-zinc-400">
                <a href="{{ route('bus-ticket-cancellations.index') }}" class="hover:text-royal-600" wire:navigate>Cancelación de boletos</a>
                <span>/</span>
                <span>{{ $isEditing ? 'Editar' : 'Nuevo' }}</span>
            </div>
            <h1 class="page-title">{{ $isEditing ? 'Editar cancelación' : 'Nueva cancelación' }}</h1>
            <p class="page-subtitle">
                {{ $companyName ? 'Registro operativo de '.$companyName.'.' : 'Registra una cancelación de boleto.' }}
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

    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
        Este módulo registra y reporta la gestión realizada manualmente. No modifica boletos en sistemas externos.
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="surface-card p-5 sm:p-6">
            <div class="mb-5">
                <h2 class="text-sm font-semibold text-navy-900 dark:text-zinc-100">Boleto</h2>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    Identifica el boleto cancelado y, si aplica, la solicitud de soporte relacionada.
                </p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <flux:input
                        wire:model="ticketNumber"
                        label="Número o identificador del boleto"
                        placeholder="Ej. BOL-00012345"
                        maxlength="100"
                        required
                        autofocus
                    />
                </div>

                <flux:select wire:model="supportTicketId" label="Ticket de soporte">
                    <option value="">Sin ticket relacionado</option>
                    @foreach($ticketOptions as $ticket)
                        <option value="{{ $ticket['id'] }}">{{ $ticket['label'] }}</option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="originalTicketAt" label="Fecha original del boleto" type="datetime-local" />
            </div>
        </div>

        <div class="surface-card p-5 sm:p-6">
            <div class="mb-5">
                <h2 class="text-sm font-semibold text-navy-900 dark:text-zinc-100">Gestión</h2>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    Registra cuándo se solicitó, cuándo se ejecutó y quién fue el operador responsable.
                </p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <flux:input wire:model="requestedAt" label="Fecha y hora de solicitud" type="datetime-local" required />
                <flux:input wire:model="executedAt" label="Fecha y hora de ejecución" type="datetime-local" />
                <flux:select wire:model.live="result" label="Resultado" required>
                    @foreach($resultOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="operatorId" label="Operador responsable" required>
                    <option value="">Selecciona un operador</option>
                    @foreach($operatorOptions as $operator)
                        <option value="{{ $operator['id'] }}">{{ $operator['name'] }}</option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        <div class="surface-card p-5 sm:p-6">
            <div class="mb-5">
                <h2 class="text-sm font-semibold text-navy-900 dark:text-zinc-100">Importe y autorización</h2>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    El importe y la referencia de autorización son opcionales cuando no apliquen.
                </p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <flux:input wire:model="amount" label="Importe (MXN)" type="number" step="0.01" min="0" placeholder="0.00" />
                <flux:input wire:model="authorizationReference" label="Referencia de autorización" placeholder="Folio, autorización o referencia interna" maxlength="150" />
            </div>
        </div>

        <div class="surface-card p-5 sm:p-6">
            <div class="mb-5">
                <h2 class="text-sm font-semibold text-navy-900 dark:text-zinc-100">Detalle</h2>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    Describe el motivo y cualquier referencia de evidencia disponible.
                </p>
            </div>

            <div class="grid gap-5">
                <flux:textarea wire:model="reason" label="Motivo" rows="4" maxlength="2000" required />
                <flux:textarea wire:model="evidenceNotes" label="Notas de evidencia" rows="3" maxlength="2000" placeholder="Ej. comprobante físico resguardado en recepción." />
            </div>
        </div>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
            <flux:button :href="route('bus-ticket-cancellations.index')" variant="ghost" wire:navigate>
                Cancelar
            </flux:button>

            <flux:button variant="primary" type="submit" icon="check">
                {{ $isEditing ? 'Guardar cambios' : 'Registrar cancelación' }}
            </flux:button>
        </div>
    </form>
</div>
