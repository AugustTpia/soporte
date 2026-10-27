<?php

namespace Database\Factories;

use App\Assets\Domain\Enums\AssetCategory;
use App\Assets\Domain\Enums\AssetStatus;
use App\Assets\Infrastructure\Persistence\Eloquent\Models\AssetModel;
use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use App\Locations\Infrastructure\Persistence\Eloquent\Models\LocationModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetModel>
 */
class AssetFactory extends Factory
{
    protected $model = AssetModel::class;

    public function definition(): array
    {
        return [
            'company_id' => CompanyModel::factory(),
            'internal_code' => 'ACT-'.fake()->unique()->numerify('######'),
            'hostname' => fake()->unique()->bothify('pc-####'),
            'category' => fake()->randomElement(AssetCategory::cases())->value,
            'brand' => fake()->randomElement(['Dell', 'HP', 'Lenovo', 'Apple', 'Cisco']),
            'model' => strtoupper(fake()->bothify('??-####')),
            'serial_number' => strtoupper(fake()->bothify('??########')),
            'location_id' => LocationModel::factory(),
            'assigned_user_id' => null,
            'status' => AssetStatus::Active->value,
            'operating_system' => fake()->randomElement(['Windows 11 Pro', 'Ubuntu 24.04', 'macOS Sonoma']),
            'ip_address' => fake()->localIpv4(),
            'purchase_date' => fake()->dateTimeBetween('-3 years', '-1 month')->format('Y-m-d'),
            'warranty_until' => fake()->dateTimeBetween('now', '+2 years')->format('Y-m-d'),
            'notes' => fake()->optional()->sentence(),
            'created_by' => null,
        ];
    }

    public function forCompany(CompanyModel $company, LocationModel $location): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $company->id,
            'location_id' => $location->id,
        ]);
    }
}
