<?php

namespace App\Support\Infrastructure\Persistence\Eloquent\Models;

use App\Accounts\Infrastructure\Persistence\Eloquent\Models\ServiceAccountModel;
use App\Assets\Infrastructure\Persistence\Eloquent\Models\AssetModel;
use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use App\Identity\Infrastructure\Persistence\Eloquent\User;
use App\Locations\Infrastructure\Persistence\Eloquent\Models\LocationModel;
use Carbon\CarbonImmutable;
use Database\Factories\SupportTicketFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $company_id
 * @property string|null $folio
 * @property int|null $location_id
 * @property int|null $asset_id
 * @property int|null $service_account_id
 * @property string $category
 * @property string $subject
 * @property string $description
 * @property string|null $requester_name
 * @property string|null $requester_contact
 * @property string $priority
 * @property string $status
 * @property CarbonImmutable $opened_at
 * @property CarbonImmutable|null $due_at
 * @property CarbonImmutable|null $resolved_at
 * @property CarbonImmutable|null $closed_at
 * @property CarbonImmutable|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read CompanyModel $company
 * @property-read LocationModel|null $location
 * @property-read AssetModel|null $asset
 * @property-read ServiceAccountModel|null $serviceAccount
 * @property-read User|null $creator
 * @property-read Collection<int, TicketAssignmentModel> $assignments
 * @property-read Collection<int, TicketAssignmentModel> $activeAssignments
 * @property-read Collection<int, TicketActivityModel> $activities
 */
class SupportTicketModel extends Model
{
    /** @use HasFactory<SupportTicketFactory> */
    use HasFactory;

    protected $table = 'support_tickets';

    protected $fillable = [
        'company_id',
        'folio',
        'location_id',
        'asset_id',
        'service_account_id',
        'category',
        'subject',
        'description',
        'requester_name',
        'requester_contact',
        'priority',
        'status',
        'opened_at',
        'due_at',
        'resolved_at',
        'closed_at',
        'cancelled_at',
        'cancellation_reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'due_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function newFactory(): SupportTicketFactory
    {
        return SupportTicketFactory::new();
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
     * @return BelongsTo<AssetModel, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(AssetModel::class, 'asset_id');
    }

    /**
     * @return BelongsTo<ServiceAccountModel, $this>
     */
    public function serviceAccount(): BelongsTo
    {
        return $this->belongsTo(ServiceAccountModel::class, 'service_account_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<TicketAssignmentModel, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(TicketAssignmentModel::class, 'ticket_id')
            ->orderByDesc('assigned_at');
    }

    /**
     * @return HasMany<TicketAssignmentModel, $this>
     */
    public function activeAssignments(): HasMany
    {
        return $this->hasMany(TicketAssignmentModel::class, 'ticket_id')
            ->whereNull('unassigned_at')
            ->orderBy('assigned_at');
    }

    /**
     * @return HasMany<TicketActivityModel, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(TicketActivityModel::class, 'ticket_id')
            ->orderByDesc('created_at');
    }
}
