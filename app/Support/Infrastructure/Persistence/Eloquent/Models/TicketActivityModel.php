<?php

namespace App\Support\Infrastructure\Persistence\Eloquent\Models;

use App\Identity\Infrastructure\Persistence\Eloquent\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $ticket_id
 * @property int|null $actor_id
 * @property string $type
 * @property string|null $body
 * @property array<string, mixed>|null $metadata
 * @property CarbonImmutable|null $created_at
 * @property-read SupportTicketModel $ticket
 * @property-read User|null $actor
 */
class TicketActivityModel extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'ticket_activities';

    protected $fillable = [
        'ticket_id',
        'actor_id',
        'type',
        'body',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
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
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
