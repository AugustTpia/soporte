<?php

namespace App\Accounts\Infrastructure\Persistence\Eloquent\Models;

use App\Assets\Infrastructure\Persistence\Eloquent\Models\AssetModel;
use App\Identity\Infrastructure\Persistence\Eloquent\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $service_account_id
 * @property int $asset_id
 * @property CarbonImmutable $linked_at
 * @property CarbonImmutable|null $unlinked_at
 * @property string|null $reason
 * @property string|null $unlink_reason
 * @property int|null $linked_by
 * @property int|null $unlinked_by
 * @property-read ServiceAccountModel $account
 * @property-read AssetModel|null $asset
 * @property-read User|null $linkedBy
 * @property-read User|null $unlinkedBy
 */
class ServiceAccountAssetLinkModel extends Model
{
    protected $table = 'service_account_asset_links';

    protected $fillable = [
        'service_account_id',
        'asset_id',
        'linked_at',
        'unlinked_at',
        'reason',
        'unlink_reason',
        'linked_by',
        'unlinked_by',
    ];

    protected function casts(): array
    {
        return [
            'linked_at' => 'datetime',
            'unlinked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ServiceAccountModel, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(ServiceAccountModel::class, 'service_account_id');
    }

    /**
     * @return BelongsTo<AssetModel, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(AssetModel::class, 'asset_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function linkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'linked_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function unlinkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unlinked_by');
    }
}
