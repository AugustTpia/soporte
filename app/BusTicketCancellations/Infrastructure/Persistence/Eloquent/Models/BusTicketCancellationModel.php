<?php

namespace App\BusTicketCancellations\Infrastructure\Persistence\Eloquent\Models;

use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use App\Identity\Infrastructure\Persistence\Eloquent\User;
use App\Support\Infrastructure\Persistence\Eloquent\Models\SupportTicketModel;
use Carbon\CarbonImmutable;
use Database\Factories\BusTicketCancellationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $company_id
 * @property int|null $support_ticket_id
 * @property string $ticket_number
 * @property CarbonImmutable $requested_at
 * @property CarbonImmutable|null $executed_at
 * @property CarbonImmutable|null $original_ticket_at
 * @property int|null $amount_cents
 * @property int $operator_id
 * @property string $result
 * @property string $reason
 * @property string|null $authorization_reference
 * @property string|null $evidence_notes
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read CompanyModel $company
 * @property-read SupportTicketModel|null $supportTicket
 * @property-read User $operator
 * @property-read User|null $creator
 */
class BusTicketCancellationModel extends Model
{
    /** @use HasFactory<BusTicketCancellationFactory> */
    use HasFactory;

    protected $table = 'bus_ticket_cancellations';

    protected $fillable = [
        'company_id',
        'support_ticket_id',
        'ticket_number',
        'requested_at',
        'executed_at',
        'original_ticket_at',
        'amount_cents',
        'operator_id',
        'result',
        'reason',
        'authorization_reference',
        'evidence_notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'executed_at' => 'datetime',
            'original_ticket_at' => 'datetime',
            'amount_cents' => 'integer',
        ];
    }

    protected static function newFactory(): BusTicketCancellationFactory
    {
        return BusTicketCancellationFactory::new();
    }

    /**
     * @return BelongsTo<CompanyModel, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(CompanyModel::class, 'company_id');
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
    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
