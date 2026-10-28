<?php

namespace App\Maintenance\Infrastructure\Persistence\Eloquent\Models;

use App\Assets\Infrastructure\Persistence\Eloquent\Models\AssetModel;
use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use App\Identity\Infrastructure\Persistence\Eloquent\User;
use App\Locations\Infrastructure\Persistence\Eloquent\Models\LocationModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $company_id
 * @property string $target_type
 * @property int|null $asset_id
 * @property int|null $location_id
 * @property string $target_label
 * @property string $title
 * @property string|null $description
 * @property int $interval_value
 * @property string $interval_unit
 * @property CarbonImmutable $next_due_at
 * @property CarbonImmutable|null $last_completed_at
 * @property int|null $assigned_user_id
 * @property string $status
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read CompanyModel $company
 * @property-read AssetModel|null $asset
 * @property-read LocationModel|null $location
 * @property-read User|null $assignee
 * @property-read User|null $creator
 * @property-read Collection<int, MaintenancePlanChecklistItemModel> $checklistItems
 * @property-read Collection<int, MaintenanceExecutionModel> $executions
 */
final class MaintenancePlanModel extends Model
{
    protected $table = 'maintenance_plans';

    protected $fillable = [
        'company_id',
        'target_type',
        'asset_id',
        'location_id',
        'target_label',
        'title',
        'description',
        'interval_value',
        'interval_unit',
        'next_due_at',
        'last_completed_at',
        'assigned_user_id',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'asset_id' => 'integer',
            'location_id' => 'integer',
            'interval_value' => 'integer',
            'next_due_at' => 'immutable_datetime',
            'last_completed_at' => 'immutable_datetime',
            'assigned_user_id' => 'integer',
            'created_by' => 'integer',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
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
    public function assignee(): BelongsTo
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
     * @return HasMany<MaintenancePlanChecklistItemModel, $this>
     */
    public function checklistItems(): HasMany
    {
        return $this->hasMany(MaintenancePlanChecklistItemModel::class, 'maintenance_plan_id')
            ->orderBy('sort_order');
    }

    /**
     * @return HasMany<MaintenanceExecutionModel, $this>
     */
    public function executions(): HasMany
    {
        return $this->hasMany(MaintenanceExecutionModel::class, 'maintenance_plan_id')
            ->orderByDesc('scheduled_at');
    }
}
