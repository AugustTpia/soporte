<?php

namespace App\Auditing\Infrastructure\Logging;

use App\Auditing\Infrastructure\Persistence\Eloquent\Models\AuditLogModel;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Application\Contracts\TenantContext;
use Illuminate\Support\Facades\Auth;

final readonly class DatabaseAuditLogger implements AuditLogger
{
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'access_token',
        'refresh_token',
        'api_token',
        'secret',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
        'authorization',
        'cookie',
    ];

    public function __construct(private TenantContext $tenant) {}

    public function log(
        string $event,
        string $description,
        ?string $subjectType = null,
        int|string|null $subjectId = null,
        array $oldValues = [],
        array $newValues = [],
        array $metadata = [],
        ?int $companyId = null,
    ): void {
        AuditLogModel::query()->create([
            'company_id' => $companyId ?? $this->tenant->companyId(),
            'actor_id' => Auth::id(),
            'event' => $event,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'description' => $description,
            'old_values' => $oldValues === [] ? null : $this->sanitize($oldValues),
            'new_values' => $newValues === [] ? null : $this->sanitize($newValues),
            'metadata' => $metadata === [] ? null : $this->sanitize($metadata),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function sanitize(array $values): array
    {
        $clean = [];

        foreach ($values as $key => $value) {
            if (in_array(mb_strtolower((string) $key), self::SENSITIVE_KEYS, true)) {
                continue;
            }

            $clean[$key] = is_array($value) ? $this->sanitize($value) : $value;
        }

        return $clean;
    }
}
