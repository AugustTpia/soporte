<?php

namespace App\Locations\Infrastructure\Persistence\Eloquent\Models;

use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string $slug
 * @property string $street
 * @property string $exterior_number
 * @property string|null $interior_number
 * @property string|null $neighborhood
 * @property string $city
 * @property string|null $state
 * @property string|null $postal_code
 * @property string $country
 * @property string|null $phone
 * @property string|null $notes
 * @property bool $is_active
 * @property-read CompanyModel $company
 */
class LocationModel extends Model
{
    /** @use HasFactory<LocationFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'locations';

    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'street',
        'exterior_number',
        'interior_number',
        'neighborhood',
        'city',
        'state',
        'postal_code',
        'country',
        'phone',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function newFactory(): LocationFactory
    {
        return LocationFactory::new();
    }

    /**
     * @return BelongsTo<CompanyModel, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(CompanyModel::class, 'company_id');
    }
}
