<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="mb-2 flex items-center gap-2 text-xs text-zinc-400">
                <a href="{{ route('credentials.index') }}" class="hover:text-royal-600" wire:navigate>Credenciales</a>
                <span>/</span>
                <span>{{ $isEditing ? ($canManage ? 'Editar' : 'Detalle') : 'Nueva' }}</span>
            </div>
            <h1 class="page-title">
                {{ $isEditing ? ($canManage ? 'Editar credencial' : 'Detalle de credencial') : 'Nueva credencial' }}
            </h1>
            <p class="page-subtitle">
                {{ $companyName ? 'Credenciales protegidas de '.$companyName.'.' : 'Registra una referencia de credencial.' }}
            </p>
        </div>
    </div>

    @if(session('status'))
        <div class="rounded-2xl border border-emerald-200 dark:border-emerald-500/30 bg-emerald-50 dark:bg-emerald-500/10 p-4 text-sm text-emerald-700 dark:text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

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

    <div class="rounded-2xl border border-sky-200 dark:border-sky-500/30 bg-sky-50 dark:bg-sky-500/10 p-4 text-sm text-sky-800 dark:text-sky-300">
        <p class="font-medium">La contraseña se cifra y nunca se muestra en esta ficha.</p>
        <p class="mt-1">
            Solo personal autorizado puede revelarla. Cada intento requiere tu contraseña actual y queda auditado.
        </p>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="surface-card p-5 sm:p-6">
            <div class="mb-5">
                <h2 class="text-sm font-semibold text-navy-900 dark:text-zinc-100">Datos de la credencial</h2>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    La etiqueta identifica el secreto dentro de la cuenta de servicio.
                </p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    @if($isEditing)
                        <flux:input
                            :value="$accountLabel"
                            label="Cuenta de servicio"
                            readonly
                        />
                    @else
                        <flux:select wire:model="serviceAccountId" label="Cuenta de servicio" required>
                            <option value="">Selecciona una cuenta</option>
                            @foreach($accountOptions as $option)
                                <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </flux:select>
                    @endif
                </div>

                <flux:input
                    wire:model="label"
                    label="Etiqueta"
                    placeholder="Ej. Contraseña principal, token de respaldo..."
                    maxlength="120"
                    :disabled="!$canManage"
                    required
                />

                <flux:select wire:model="kind" label="Tipo de secreto" :disabled="!$canManage" required>
                    @foreach($kindOptions as $value => $labelKind)
                        <option value="{{ $value }}">{{ $labelKind }}</option>
                    @endforeach
                </flux:select>

                @if($isEditing)
                    <flux:select wire:model="status" label="Estado" :disabled="!$canManage" required>
                        @foreach($statusOptions as $value => $labelStatus)
                            <option value="{{ $value }}">{{ $labelStatus }}</option>
                        @endforeach
                    </flux:select>
                @endif
            </div>

            @if(!$canManage)
                <p class="mt-4 text-xs text-zinc-500 dark:text-zinc-400">
                    Tienes acceso de consulta. Solo los perfiles autorizados pueden modificar metadatos o secretos.
                </p>
            @endif
        </div>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
            <flux:button :href="route('credentials.index')" variant="ghost" wire:navigate>
                Volver
            </flux:button>

            @if($canManage)
                <flux:button variant="primary" type="submit" icon="check">
                    {{ $isEditing ? 'Guardar cambios' : 'Crear credencial' }}
                </flux:button>
            @endif
        </div>
    </form>

    @if($isEditing && $credential)
        <div class="surface-card p-5 sm:p-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-navy-900 dark:text-zinc-100">Secreto</h2>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        El valor cifrado no se incluye en esta página ni en los listados.
                    </p>
                </div>

                @if($credential->hasSecret)
                    <flux:badge color="green" size="sm">Configurado</flux:badge>
                @else
                    <flux:badge color="amber" size="sm">Pendiente</flux:badge>
                @endif
            </div>

            @if($credential->status === \App\Credentials\Domain\Enums\CredentialStatus::Revoked)
                <div class="mt-4 rounded-xl border border-rose-200 dark:border-rose-500/30 bg-rose-50 dark:bg-rose-500/10 p-4 text-sm text-rose-700 dark:text-rose-300">
                    La credencial está revocada. No se puede almacenar, rotar ni revelar su secreto.
                </div>
            @elseif(!$vaultConfigured)
                <div class="mt-4 rounded-xl border border-amber-200 dark:border-amber-500/30 bg-amber-50 dark:bg-amber-500/10 p-4 text-sm text-amber-800 dark:text-amber-300">
                    La bóveda no está configurada. Define <span class="font-mono">CREDENTIALS_ENCRYPTION_KEY</span> antes de guardar o revelar secretos.
                </div>
            @else
                @if($credential->lastRotatedAt)
                    <p class="mt-4 text-xs text-zinc-500 dark:text-zinc-400">
                        Última rotación: {{ $credential->lastRotatedAt->format('d/m/Y H:i') }}
                    </p>
                @endif

                @if($canManage)
                    <form
                        method="POST"
                        action="{{ route('credentials.secret.store', $credential->id) }}"
                        class="mt-5 space-y-4 border-t border-zinc-100 dark:border-zinc-800 pt-5"
                        autocomplete="off"
                    >
                        @csrf

                        <div class="grid gap-4 sm:grid-cols-2">
                            <flux:input
                                type="password"
                                name="current_password"
                                label="Tu contraseña actual"
                                autocomplete="current-password"
                                required
                            />

                            <div></div>

                            <flux:input
                                type="password"
                                name="secret"
                                label="{{ $credential->hasSecret ? 'Nuevo secreto' : 'Secreto' }}"
                                autocomplete="new-password"
                                required
                            />

                            <flux:input
                                type="password"
                                name="secret_confirmation"
                                label="Confirmar secreto"
                                autocomplete="new-password"
                                required
                            />
                        </div>

                        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                Se guardará cifrado con una clave dedicada. No se registra en auditoría.
                            </p>

                            <flux:button type="submit" variant="primary" icon="lock-closed">
                                {{ $credential->hasSecret ? 'Rotar secreto' : 'Guardar secreto' }}
                            </flux:button>
                        </div>
                    </form>
                @endif

                @if($canReveal && $credential->hasSecret)
                    <div
                        class="mt-6 border-t border-zinc-100 dark:border-zinc-800 pt-5"
                        x-data="{
                            csrf: '{{ csrf_token() }}',
                            password: '',
                            secret: '',
                            error: '',
                            copied: false,
                            loading: false,
                            remaining: 0,
                            countdown: null,
                            async reveal() {
                                this.error = '';
                                this.copied = false;
                                this.loading = true;

                                try {
                                    const response = await fetch('{{ route('credentials.reveal', $credential->id) }}', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'Accept': 'application/json',
                                            'X-CSRF-TOKEN': this.csrf,
                                        },
                                        body: JSON.stringify({ current_password: this.password }),
                                    });

                                    const data = await response.json().catch(() => ({}));
                                    this.password = '';

                                    if (!response.ok) {
                                        this.error = data?.errors?.current_password?.[0]
                                            || data?.message
                                            || 'No se pudo revelar la credencial.';
                                        return;
                                    }

                                    this.secret = data.secret;
                                    this.startTimer();
                                } catch (exception) {
                                    this.error = 'No se pudo conectar con el servidor.';
                                } finally {
                                    this.loading = false;
                                }
                            },
                            startTimer() {
                                this.clearTimer();
                                this.remaining = 30;
                                this.countdown = setInterval(() => {
                                    this.remaining -= 1;

                                    if (this.remaining <= 0) {
                                        this.hide();
                                    }
                                }, 1000);
                            },
                            clearTimer() {
                                if (this.countdown !== null) {
                                    clearInterval(this.countdown);
                                    this.countdown = null;
                                }
                            },
                            hide() {
                                this.secret = '';
                                this.password = '';
                                this.copied = false;
                                this.remaining = 0;
                                this.clearTimer();
                            },
                            async copy() {
                                if (!this.secret) {
                                    return;
                                }

                                try {
                                    await navigator.clipboard.writeText(this.secret);
                                    this.copied = true;
                                    setTimeout(() => this.copied = false, 2000);
                                } catch (exception) {
                                    this.error = 'No se pudo copiar al portapapeles.';
                                }
                            },
                        }"
                    >
                        <h3 class="text-sm font-semibold text-navy-900 dark:text-zinc-100">Revelar secreto</h3>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            Confirma tu identidad. El valor se ocultará automáticamente después de 30 segundos.
                        </p>

                        <form class="mt-4" x-on:submit.prevent="reveal" autocomplete="off">
                            <div class="grid items-end gap-3 sm:grid-cols-[1fr_auto]">
                                <flux:input
                                    type="password"
                                    x-model="password"
                                    label="Contraseña actual"
                                    autocomplete="current-password"
                                    required
                                />

                                <flux:button type="submit" variant="primary" icon="eye" x-bind:disabled="loading">
                                    <span x-show="!loading">Revelar</span>
                                    <span x-show="loading" x-cloak>Verificando...</span>
                                </flux:button>
                            </div>
                        </form>

                        <p class="mt-3 text-sm text-rose-600 dark:text-rose-400" x-show="error" x-text="error" x-cloak></p>

                        <div class="mt-4 rounded-xl border border-amber-200 dark:border-amber-500/30 bg-amber-50 dark:bg-amber-500/10 p-4" x-show="secret" x-cloak>
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <p class="font-mono text-sm break-all text-navy-900 dark:text-zinc-100" x-text="secret"></p>

                                <div class="flex shrink-0 items-center gap-2">
                                    <flux:button type="button" size="sm" variant="subtle" icon="clipboard" x-on:click="copy">
                                        <span x-show="!copied">Copiar</span>
                                        <span x-show="copied" x-cloak>Copiado</span>
                                    </flux:button>

                                    <flux:button type="button" size="sm" variant="subtle" icon="eye-slash" x-on:click="hide">
                                        Ocultar
                                    </flux:button>
                                </div>
                            </div>

                            <p class="mt-3 text-xs text-amber-700 dark:text-amber-300">
                                Se ocultará en <span x-text="remaining"></span> segundos. No lo compartas ni lo guardes en lugares no autorizados.
                            </p>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    @endif
</div>
