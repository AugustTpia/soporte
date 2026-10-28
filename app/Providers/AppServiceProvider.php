<?php

namespace App\Providers;

use App\Accounts\Domain\Repositories\ServiceAccountRepository;
use App\Accounts\Infrastructure\Persistence\Eloquent\Models\ServiceAccountModel;
use App\Accounts\Infrastructure\Persistence\Eloquent\Repositories\EloquentServiceAccountRepository;
use App\Assets\Domain\Repositories\AssetRepository;
use App\Assets\Infrastructure\Persistence\Eloquent\Models\AssetModel;
use App\Assets\Infrastructure\Persistence\Eloquent\Repositories\EloquentAssetRepository;
use App\Attachments\Application\Contracts\EvidenceFileStorage;
use App\Attachments\Infrastructure\Files\LocalEvidenceFileStorage;
use App\Auditing\Domain\Repositories\AuditRepository;
use App\Auditing\Infrastructure\Logging\DatabaseAuditLogger;
use App\Auditing\Infrastructure\Persistence\Eloquent\Models\AuditLogModel;
use App\Auditing\Infrastructure\Persistence\Eloquent\Repositories\EloquentAuditRepository;
use App\BusTicketCancellations\Domain\Repositories\BusTicketCancellationAttachmentRepository;
use App\BusTicketCancellations\Domain\Repositories\BusTicketCancellationRepository;
use App\BusTicketCancellations\Infrastructure\Persistence\Eloquent\Models\BusTicketCancellationModel;
use App\BusTicketCancellations\Infrastructure\Persistence\Eloquent\Repositories\EloquentBusTicketCancellationAttachmentRepository;
use App\BusTicketCancellations\Infrastructure\Persistence\Eloquent\Repositories\EloquentBusTicketCancellationRepository;
use App\Companies\Domain\Repositories\CompanyRepository;
use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use App\Companies\Infrastructure\Persistence\Eloquent\Repositories\EloquentCompanyRepository;
use App\Companies\Presentation\Livewire\Companies\CompanySwitcher;
use App\Credentials\Application\Contracts\CredentialCipher;
use App\Credentials\Domain\Repositories\CredentialRepository;
use App\Credentials\Infrastructure\Encryption\LaravelCredentialCipher;
use App\Credentials\Infrastructure\Persistence\Eloquent\Models\CredentialModel;
use App\Credentials\Infrastructure\Persistence\Eloquent\Repositories\EloquentCredentialRepository;
use App\Departments\Domain\Repositories\DepartmentRepository;
use App\Departments\Infrastructure\Persistence\Eloquent\Models\DepartmentModel;
use App\Departments\Infrastructure\Persistence\Eloquent\Repositories\EloquentDepartmentRepository;
use App\Identity\Application\Contracts\PasswordVerifier;
use App\Identity\Domain\Repositories\UserRepository;
use App\Identity\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRepository;
use App\Identity\Infrastructure\Persistence\Eloquent\User;
use App\Identity\Infrastructure\Security\LaravelPasswordVerifier;
use App\Locations\Domain\Repositories\LocationRepository;
use App\Locations\Infrastructure\Persistence\Eloquent\Models\LocationModel;
use App\Locations\Infrastructure\Persistence\Eloquent\Repositories\EloquentLocationRepository;
use App\Policies\AssetPolicy;
use App\Policies\AuditLogPolicy;
use App\Policies\BusTicketCancellationPolicy;
use App\Policies\CompanyPolicy;
use App\Policies\CredentialPolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\LocationPolicy;
use App\Policies\ServiceAccountPolicy;
use App\Policies\TicketPolicy;
use App\Policies\UserPolicy;
use App\Presentation\Http\Middleware\ResolveTenant;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Application\Contracts\TenantContext;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Infrastructure\Persistence\LaravelTransactionManager;
use App\Shared\Infrastructure\Tenancy\SessionTenantContext;
use App\Support\Domain\Repositories\TicketAttachmentRepository;
use App\Support\Domain\Repositories\TicketRepository;
use App\Support\Infrastructure\Persistence\Eloquent\Models\SupportTicketModel;
use App\Support\Infrastructure\Persistence\Eloquent\Repositories\EloquentTicketAttachmentRepository;
use App\Support\Infrastructure\Persistence\Eloquent\Repositories\EloquentTicketRepository;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantContext::class, SessionTenantContext::class);
        $this->app->singleton(AuditLogger::class, DatabaseAuditLogger::class);
        $this->app->bind(TransactionManager::class, LaravelTransactionManager::class);

        $this->app->bind(ServiceAccountRepository::class, EloquentServiceAccountRepository::class);
        $this->app->bind(BusTicketCancellationRepository::class, EloquentBusTicketCancellationRepository::class);
        $this->app->bind(BusTicketCancellationAttachmentRepository::class, EloquentBusTicketCancellationAttachmentRepository::class);
        $this->app->bind(EvidenceFileStorage::class, LocalEvidenceFileStorage::class);
        $this->app->bind(TicketRepository::class, EloquentTicketRepository::class);
        $this->app->bind(TicketAttachmentRepository::class, EloquentTicketAttachmentRepository::class);
        $this->app->bind(CredentialRepository::class, EloquentCredentialRepository::class);
        $this->app->singleton(CredentialCipher::class, fn () => new LaravelCredentialCipher(
            currentKey: config('credentials.current_key'),
            previousKeys: config('credentials.previous_keys', []),
        ));
        $this->app->bind(PasswordVerifier::class, LaravelPasswordVerifier::class);
        $this->app->bind(AssetRepository::class, EloquentAssetRepository::class);
        $this->app->bind(CompanyRepository::class, EloquentCompanyRepository::class);
        $this->app->bind(DepartmentRepository::class, EloquentDepartmentRepository::class);
        $this->app->bind(LocationRepository::class, EloquentLocationRepository::class);
        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
        $this->app->bind(AuditRepository::class, EloquentAuditRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthorization();
        $this->configureEvents();
        $this->configureLivewire();
        $this->configureRateLimiting();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : Password::min(8),
        );
    }

    protected function configureAuthorization(): void
    {
        Gate::before(function (?User $user): ?bool {
            return $user?->isSuperAdmin() ? true : null;
        });

        Gate::policy(CompanyModel::class, CompanyPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(DepartmentModel::class, DepartmentPolicy::class);
        Gate::policy(LocationModel::class, LocationPolicy::class);
        Gate::policy(AssetModel::class, AssetPolicy::class);
        Gate::policy(ServiceAccountModel::class, ServiceAccountPolicy::class);
        Gate::policy(SupportTicketModel::class, TicketPolicy::class);
        Gate::policy(BusTicketCancellationModel::class, BusTicketCancellationPolicy::class);
        Gate::policy(CredentialModel::class, CredentialPolicy::class);
        Gate::policy(AuditLogModel::class, AuditLogPolicy::class);
    }

    protected function configureEvents(): void
    {
        Event::listen(Login::class, function (Login $event): void {
            if ($event->user instanceof User) {
                $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
            }
        });
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('credentials', fn (Request $request) => Limit::perMinute(6)
            ->by($request->user()?->id ?: $request->ip()));
    }

    protected function configureLivewire(): void
    {
        Livewire::component('companies.company-switcher', CompanySwitcher::class);

        Livewire::addPersistentMiddleware([
            ResolveTenant::class,
        ]);
    }
}
