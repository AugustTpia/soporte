<?php

namespace Database\Factories;

use App\Accounts\Domain\Enums\ServiceAccountStatus;
use App\Accounts\Domain\Enums\ServiceType;
use App\Accounts\Infrastructure\Persistence\Eloquent\Models\ServiceAccountModel;
use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceAccountModel>
 */
class ServiceAccountFactory extends Factory
{
    protected $model = ServiceAccountModel::class;

    public function definition(): array
    {
        return [
            'company_id' => CompanyModel::factory(),
            'service' => ServiceType::Nextcloud->value,
            'username' => fake()->unique()->bothify('usuario-####'),
            'instance' => 'cloud.example.test',
            'owner_name' => fake()->name(),
            'status' => ServiceAccountStatus::Active->value,
            'responsible_user_id' => null,
            'notes' => fake()->optional()->sentence(),
            'created_by' => null,
        ];
    }

    public function forCompany(CompanyModel $company): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $company->id,
        ]);
    }
}
