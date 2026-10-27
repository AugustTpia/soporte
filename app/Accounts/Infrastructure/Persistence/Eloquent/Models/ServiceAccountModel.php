<?php

namespace App\Accounts\Infrastructure\Persistence\Eloquent\Models;

use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use App\Credentials\Infrastructure\Persistence\Eloquent\Models\CredentialModel;
use App\Identity\Infrastructure\Persistence\Eloquent\User;
use Database\Factories\ServiceAccountFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $company_id
 * @property string $service
 * @property string $username
 * @property string $instance
 * @property string|null $owner_name
 * @property string $status
 * @property int|null $responsible_user_id
 * @property string|null $notes
 * @property int|null $created_by
 * @property-read CompanyModel $company
 * @property-read User|null $responsibleUser
 * @property-read User|null $creator
 * @property-read Collection<int, ServiceAccountAssetLinkModel> $links
 * @property-read Collection<int, CredentialModel> $credentials
 */
class ServiceAccountModel extends Model
{
    /** @use HasFactory<ServiceAccountFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'service_accounts';

    protected $fillable = [
        'company_id',
        'service',
        'username',
        'instance',
        'owner_name',
        'status',
        'responsible_user_id',
        'notes',
        'created_by',
    ];

    protected static function newFactory(): ServiceAccountFactory
    {
        return ServiceAccountFactory::new();
    }

    /**
     * @return BelongsTo<CompanyModel, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(CompanyModel::class, 'company_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<ServiceAccountAssetLinkModel, $this>
     */
    public function links(): HasMany
    {
        return $this->hasMany(ServiceAccountAssetLinkModel::class, 'service_account_id')
            ->orderByDesc('linked_at');
    }

    /**
     * @return HasMany<CredentialModel, $this>
     */
    public function credentials(): HasMany
    {
        return $this->hasMany(CredentialModel::class, 'service_account_id')
            ->orderBy('label');
    }
}
