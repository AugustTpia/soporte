<?php

namespace App\Companies\Infrastructure\Persistence\Eloquent\Models;

use App\Departments\Infrastructure\Persistence\Eloquent\Models\DepartmentModel;
use App\Identity\Infrastructure\Persistence\Eloquent\User;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $public_id
 * @property string $name
 * @property string|null $legal_name
 * @property string|null $tax_id
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $website
 * @property string|null $industry
 * @property string $status
 * @property string $timezone
 * @property string $locale
 * @property string|null $logo_path
 * @property array<string, mixed>|null $settings
 * @property int|null $created_by
 * @property-read User|null $creator
 * @property-read Collection<int, User> $users
 * @property-read Collection<int, DepartmentModel> $departments
 * @property-read int|null $users_count
 * @property-read int|null $departments_count
 */
class CompanyModel extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'companies';

    protected $fillable = [
        'public_id',
        'name',
        'legal_name',
        'tax_id',
        'email',
        'phone',
        'website',
        'industry',
        'status',
        'timezone',
        'locale',
        'logo_path',
        'settings',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    protected static function newFactory(): CompanyFactory
    {
        return CompanyFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function (self $company): void {
            $company->public_id ??= (string) Str::ulid();
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'company_id');
    }

    /**
     * @return HasMany<DepartmentModel, $this>
     */
    public function departments(): HasMany
    {
        return $this->hasMany(DepartmentModel::class, 'company_id');
    }
}
