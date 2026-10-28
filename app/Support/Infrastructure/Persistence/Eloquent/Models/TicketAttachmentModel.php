<?php

namespace App\Support\Infrastructure\Persistence\Eloquent\Models;

use App\Identity\Infrastructure\Persistence\Eloquent\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $company_id
 * @property int $support_ticket_id
 * @property int|null $uploaded_by
 * @property string $original_name
 * @property string $disk
 * @property string $path
 * @property string $mime_type
 * @property int $size_bytes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read SupportTicketModel $ticket
 * @property-read User|null $uploader
 */
final class TicketAttachmentModel extends Model
{
    protected $table = 'ticket_attachments';

    protected $fillable = [
        'company_id',
        'support_ticket_id',
        'uploaded_by',
        'original_name',
        'disk',
        'path',
        'mime_type',
        'size_bytes',
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'support_ticket_id' => 'integer',
            'uploaded_by' => 'integer',
            'size_bytes' => 'integer',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<SupportTicketModel, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicketModel::class, 'support_ticket_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
