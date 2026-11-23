<?php

namespace App\Projects\Infrastructure\Persistence\Eloquent\Models;

use App\Identity\Infrastructure\Persistence\Eloquent\User;
use App\Support\Infrastructure\Persistence\Eloquent\Models\SupportTicketModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $project_id
 * @property string $title
 * @property string|null $description
 * @property string $status
 * @property string $priority
 * @property int|null $assigned_user_id
 * @property CarbonImmutable|null $due_date
 * @property int|null $ticket_id
 * @property int $sort_order
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read ProjectModel $project
 * @property-read User|null $assignee
 * @property-read SupportTicketModel|null $ticket
 * @property-read User|null $creator
 */
final class ProjectTaskModel extends Model
{
    protected $table = 'project_tasks';

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'status',
        'priority',
        'assigned_user_id',
        'due_date',
        'ticket_id',
        'sort_order',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'project_id' => 'integer',
            'assigned_user_id' => 'integer',
            'due_date' => 'date',
            'ticket_id' => 'integer',
            'sort_order' => 'integer',
            'created_by' => 'integer',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<ProjectModel, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectModel::class, 'project_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * @return BelongsTo<SupportTicketModel, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicketModel::class, 'ticket_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
