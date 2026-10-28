<?php

namespace App\Maintenance\Infrastructure\Persistence\Eloquent\Models;

use App\Identity\Infrastructure\Persistence\Eloquent\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $maintenance_execution_id
 * @property int|null $maintenance_plan_checklist_item_id
 * @property string $label
 * @property bool $is_required
 * @property int $sort_order
 * @property bool $is_completed
 * @property string|null $notes
 * @property CarbonImmutable|null $completed_at
 * @property int|null $completed_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read MaintenanceExecutionModel $execution
 * @property-read User|null $completedBy
 */
final class MaintenanceExecutionChecklistItemModel extends Model
{
    protected $table = 'maintenance_execution_checklist_items';

    protected $fillable = [
        'maintenance_execution_id',
        'maintenance_plan_checklist_item_id',
        'label',
        'is_required',
        'sort_order',
        'is_completed',
        'notes',
        'completed_at',
        'completed_by',
    ];

    protected function casts(): array
    {
        return [
            'maintenance_execution_id' => 'integer',
            'maintenance_plan_checklist_item_id' => 'integer',
            'is_required' => 'boolean',
            'sort_order' => 'integer',
            'is_completed' => 'boolean',
            'completed_at' => 'immutable_datetime',
            'completed_by' => 'integer',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<MaintenanceExecutionModel, $this>
     */
    public function execution(): BelongsTo
    {
        return $this->belongsTo(MaintenanceExecutionModel::class, 'maintenance_execution_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
