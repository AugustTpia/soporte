<?php

namespace App\Contacts\Infrastructure\Persistence\Eloquent\Models;

use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use App\Locations\Infrastructure\Persistence\Eloquent\Models\LocationModel;
use Database\Factories\ClientContactFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $company_id
 * @property int|null $location_id
 * @property string $name
 * @property string|null $position
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $whatsapp
 * @property bool $is_primary
 * @property bool $is_active
 * @property string|null $notes
 * @property-read CompanyModel $company
 * @property-read LocationModel|null $location
 */
final class ClientContactModel extends Model
{
    /** @use HasFactory<ClientContactFactory> */
    use HasFactory;

    protected $table = 'client_contacts';

    protected $fillable = [
        'company_id',
        'location_id',
        'name',
        'position',
        'email',
        'phone',
        'whatsapp',
        'is_primary',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'location_id' => 'integer',
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function newFactory(): ClientContactFactory
    {
        return ClientContactFactory::new();
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
}
