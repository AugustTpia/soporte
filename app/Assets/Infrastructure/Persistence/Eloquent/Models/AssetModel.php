<?php

namespace App\Assets\Infrastructure\Persistence\Eloquent\Models;

use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use App\Identity\Infrastructure\Persistence\Eloquent\User;
use App\Locations\Infrastructure\Persistence\Eloquent\Models\LocationModel;
use Carbon\CarbonImmutable;
use Database\Factories\AssetFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $company_id
 * @property string $internal_code
 * @property string|null $hostname
 * @property string $category
 * @property string|null $brand
 * @property string|null $model
 * @property string|null $serial_number
 * @property int $location_id
 * @property int|null $assigned_user_id
 * @property string $status
 * @property string|null $operating_system
 * @property string|null $ip_address
 * @property CarbonImmutable|null $purchase_date
 * @property CarbonImmutable|null $warranty_until
 * @property string|null $notes
 * @property int|null $created_by
 * @property-read CompanyModel $company
 * @property-read LocationModel $location
 * @property-read User|null $assignedUser
 * @property-read User|null $creator
 * @property-read Collection<int, AssetAssignmentModel> $assignments
 */
class AssetModel extends Model
{
    /** @use HasFactory<AssetFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'assets';

    protected $fillable = [
        'company_id',
        'internal_code',
        'hostname',
        'category',
        'brand',
        'model',
        'serial_number',
        'location_id',
        'assigned_user_id',
        'status',
        'operating_system',
        'ip_address',
        'purchase_date',
        'warranty_until',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'warranty_until' => 'date',
        ];
    }

    protected static function newFactory(): AssetFactory
    {
        return AssetFactory::new();
    }

    /**
     * @return BelongsTo<CompanyModel, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(CompanyModel::class, 'company_id');
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

    /**
     * @return HasMany<AssetAssignmentModel, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(AssetAssignmentModel::class, 'asset_id');
    }
}
