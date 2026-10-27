<?php

use App\Companies\Domain\Repositories\CompanyRepository;
use App\Concerns\ProfileValidationRules;
use App\Shared\Application\Contracts\TenantContext;
use Flux\Flux;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Perfil')] class extends Component {
    use ProfileValidationRules;

    public string $name = '';
    public string $email = '';
    public string $jobTitle = '';
    public string $phone = '';
    public ?string $companyName = null;

    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->jobTitle = Auth::user()->job_title ?? '';
        $this->phone = Auth::user()->phone ?? '';

        $companyId = app(TenantContext::class)->companyId();

        $this->companyName = $companyId === null
            ? null
            : app(CompanyRepository::class)->find($companyId)?->name;
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            ...$this->profileRules($user->id),
            'jobTitle' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'job_title' => $validated['jobTitle'] !== '' ? $validated['jobTitle'] : null,
            'phone' => $validated['phone'] !== '' ? $validated['phone'] : null,
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Flux::toast(variant: 'success', text: 'Perfil actualizado.');
    }

    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading level="2" class="sr-only">Perfil</flux:heading>

    <x-pages::settings.layout heading="Perfil" subheading="Actualiza tus datos personales y de contacto">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-5">
            <flux:input wire:model="name" label="Nombre completo" type="text" required autofocus autocomplete="name" />

            <flux:input wire:model="email" label="Correo electrónico" type="email" required autocomplete="email" />

            @if ($this->hasUnverifiedEmail)
                <div class="rounded-xl border border-amber-200 dark:border-amber-500/30 bg-amber-50 dark:bg-amber-500/10 p-3">
                    <flux:text class="text-amber-800 dark:text-amber-300">
                        Tu correo electrónico no está verificado.

                        <flux:link class="cursor-pointer text-sm" wire:click.prevent="resendVerificationNotification">
                            Reenviar correo de verificación
                        </flux:link>
                    </flux:text>

                    @if (session('status') === 'verification-link-sent')
                        <flux:text class="mt-2 font-medium text-emerald-700 dark:text-emerald-300">
                            Se envió un nuevo enlace de verificación a tu correo.
                        </flux:text>
                    @endif
                </div>
            @endif

            <div class="grid gap-5 sm:grid-cols-2">
                <flux:input wire:model="jobTitle" label="Puesto" type="text" autocomplete="organization-title" placeholder="Ej. Técnico de soporte" />

                <flux:input wire:model="phone" label="Teléfono" type="tel" autocomplete="tel" placeholder="55 0000 0000" />
            </div>

            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-800/40 p-4 text-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-zinc-400">Contexto de acceso</p>
                <div class="mt-2 space-y-1">
                    <p class="font-medium text-navy-900 dark:text-zinc-100">{{ $companyName ?? 'Sin empresa asignada' }}</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        {{ \App\Identity\Domain\Enums\RoleName::tryFrom(Auth::user()->getRoleNames()->first() ?? '')?->label() ?? 'Sin rol' }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit" class="w-full" data-test="update-profile-button">
                    Guardar cambios
                </flux:button>
            </div>
        </form>
    </x-pages::settings.layout>
</section>
