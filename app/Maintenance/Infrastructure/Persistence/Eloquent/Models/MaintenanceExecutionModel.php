<?php

namespace App\Maintenance\Infrastructure\Persistence\Eloquent\Models;

use App\Assets\Infrastructure\Persistence\Eloquent\Models\AssetModel;
use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use App\Identity\Infrastructure\Persistence\Eloquent\User;
use App\Locations\Infrastructure\Persistence\Eloquent\Models\LocationModel;
use App\Support\Infrastructure\Persistence\Eloquent\Models\SupportTicketModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $company_id
 * @property int|null $maintenance_plan_id
 * @property int|null $asset_id
 * @property int|null $location_id
 * @property int|null $support_ticket_id
 * @property string $target_type
 * @property string $target_label
 * @property string $type
 * @property string $status
 * @property CarbonImmutable $scheduled_at
 * @property CarbonImmutable|null $started_at
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable|null $cancelled_at
 * @property int|null $technician_id
 * @property string|null $findings
 * @property string|null $actions
 * @property string|null $recommendations
 * @property string|null $parts_used
 * @property int|null $time_spent_minutes
 * @property string|null $cancellation_reason
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read CompanyModel $company
 * @property-read MaintenancePlanModel|null $plan
 * @property-read AssetModel|null $asset
 * @property-read LocationModel|null $location
 * @property-read SupportTicketModel|null $supportTicket
 * @property-read User|null $technician
 * @property-read User|null $creator
 * @property-read Collection<int, MaintenanceExecutionChecklistItemModel> $checklistItems
 * @property-read Collection<int, MaintenanceExecutionAttachmentModel> $attachments
 */
final class MaintenanceExecutionModel extends Model
{
    protected $table = 'maintenance_executions';

    protected $fillable = [
        'company_id',
        'maintenance_plan_id',
        'asset_id',
        'location_id',
        'support_ticket_id',
        'target_type',
        'target_label',
        'type',
        'status',
        'scheduled_at',
        'started_at',
        'completed_at',
        'cancelled_at',
        'technician_id',
        'findings',
        'actions',
        'recommendations',
        'parts_used',
        'time_spent_minutes',
        'cancellation_reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'maintenance_plan_id' => 'integer',
            'asset_id' => 'integer',
            'location_id' => 'integer',
            'support_ticket_id' => 'integer',
            'scheduled_at' => 'immutable_datetime',
            'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
            'technician_id' => 'integer',
            'time_spent_minutes' => 'integer',
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
     * @return BelongsTo<MaintenancePlanModel, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(MaintenancePlanModel::class, 'maintenance_plan_id');
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
     * @return BelongsTo<SupportTicketModel, $this>
     */
    public function supportTicket(): BelongsTo
    {
        return $this->belongsTo(SupportTicketModel::class, 'support_ticket_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<MaintenanceExecutionChecklistItemModel, $this>
     */
    public function checklistItems(): HasMany
    {
        return $this->hasMany(MaintenanceExecutionChecklistItemModel::class, 'maintenance_execution_id')
            ->orderBy('sort_order');
    }

    /**
     * @return HasMany<MaintenanceExecutionAttachmentModel, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(MaintenanceExecutionAttachmentModel::class, 'maintenance_execution_id')
            ->orderBy('id');
    }
}
