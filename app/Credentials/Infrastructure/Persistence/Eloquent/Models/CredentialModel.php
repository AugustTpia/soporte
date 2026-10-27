<?php

namespace App\Credentials\Infrastructure\Persistence\Eloquent\Models;

use App\Accounts\Infrastructure\Persistence\Eloquent\Models\ServiceAccountModel;
use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use App\Identity\Infrastructure\Persistence\Eloquent\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $company_id
 * @property int $service_account_id
 * @property string $label
 * @property string $kind
 * @property string $status
 * @property CarbonImmutable|null $last_rotated_at
 * @property int|null $created_by
 * @property-read CompanyModel $company
 * @property-read ServiceAccountModel $account
 * @property-read CredentialSecretModel|null $secret
 * @property-read User|null $creator
 */
class CredentialModel extends Model
{
    protected $table = 'credential_references';

    protected $fillable = [
        'company_id',
        'service_account_id',
        'label',
        'kind',
        'status',
        'last_rotated_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'last_rotated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<CompanyModel, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(CompanyModel::class, 'company_id');
    }

    /**
     * @return BelongsTo<ServiceAccountModel, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(ServiceAccountModel::class, 'service_account_id');
    }

    /**
     * @return HasOne<CredentialSecretModel, $this>
     */
    public function secret(): HasOne
    {
        return $this->hasOne(CredentialSecretModel::class, 'credential_reference_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
