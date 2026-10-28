<?php

namespace App\Maintenance\Infrastructure\Persistence\Eloquent\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $maintenance_plan_id
 * @property string $label
 * @property bool $is_required
 * @property int $sort_order
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read MaintenancePlanModel $plan
 */
final class MaintenancePlanChecklistItemModel extends Model
{
    protected $table = 'maintenance_plan_checklist_items';

    protected $fillable = [
        'maintenance_plan_id',
        'label',
        'is_required',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'maintenance_plan_id' => 'integer',
            'is_required' => 'boolean',
            'sort_order' => 'integer',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<MaintenancePlanModel, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(MaintenancePlanModel::class, 'maintenance_plan_id');
    }
}
