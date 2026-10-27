<?php

namespace App\Assets\Infrastructure\Persistence\Eloquent\Models;

use App\Identity\Infrastructure\Persistence\Eloquent\User;
use App\Locations\Infrastructure\Persistence\Eloquent\Models\LocationModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $asset_id
 * @property int $location_id
 * @property int|null $assigned_user_id
 * @property CarbonImmutable $assigned_at
 * @property CarbonImmutable|null $released_at
 * @property string|null $notes
 * @property int|null $created_by
 * @property-read AssetModel $asset
 * @property-read LocationModel $location
 * @property-read User|null $assignedUser
 * @property-read User|null $creator
 */
class AssetAssignmentModel extends Model
{
    protected $table = 'asset_assignments';

    protected $fillable = [
        'asset_id',
        'location_id',
        'assigned_user_id',
        'assigned_at',
        'released_at',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AssetModel, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(AssetModel::class, 'asset_id');
    }

    /**
     * @return BelongsTo<LocationModel, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(LocationModel::class, 'location_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
