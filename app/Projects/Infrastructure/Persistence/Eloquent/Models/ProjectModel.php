<?php

namespace App\Projects\Infrastructure\Persistence\Eloquent\Models;

use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use App\Identity\Infrastructure\Persistence\Eloquent\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string|null $description
 * @property string $status
 * @property int $manager_id
 * @property CarbonImmutable|null $start_date
 * @property CarbonImmutable|null $due_date
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read CompanyModel $company
 * @property-read User|null $manager
 * @property-read User|null $creator
 * @property-read Collection<int, ProjectMemberModel> $members
 * @property-read Collection<int, ProjectTaskModel> $tasks
 */
final class ProjectModel extends Model
{
    protected $table = 'projects';

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'status',
        'manager_id',
        'start_date',
        'due_date',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'manager_id' => 'integer',
            'start_date' => 'date',
            'due_date' => 'date',
            'created_by' => 'integer',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<CompanyModel, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(CompanyModel::class, 'company_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<ProjectMemberModel, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(ProjectMemberModel::class, 'project_id')->orderBy('id');
    }

    /**
     * @return HasMany<ProjectTaskModel, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTaskModel::class, 'project_id')->orderBy('sort_order');
    }
}
