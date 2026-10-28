<?php

namespace App\Support\Infrastructure\Persistence\Eloquent\Models;

use App\Identity\Infrastructure\Persistence\Eloquent\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $ticket_id
 * @property int $user_id
 * @property int|null $assigned_by
 * @property CarbonImmutable $assigned_at
 * @property int|null $unassigned_by
 * @property CarbonImmutable|null $unassigned_at
 * @property string|null $reason
 * @property-read SupportTicketModel $ticket
 * @property-read User $user
 * @property-read User|null $assigner
 * @property-read User|null $unassigner
 */
class TicketAssignmentModel extends Model
{
    protected $table = 'ticket_assignments';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'assigned_by',
        'assigned_at',
        'unassigned_by',
        'unassigned_at',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'unassigned_at' => 'datetime',
        ];
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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function unassigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unassigned_by');
    }
}
