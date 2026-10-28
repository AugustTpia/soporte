<?php

namespace Database\Factories;

use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use App\Contacts\Infrastructure\Persistence\Eloquent\Models\ClientContactModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientContactModel>
 */
class ClientContactFactory extends Factory
{
    protected $model = ClientContactModel::class;

    public function definition(): array
    {
        return [
            'company_id' => CompanyModel::factory(),
            'location_id' => null,
            'name' => fake()->name(),
            'position' => fake()->jobTitle(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('55########'),
            'whatsapp' => null,
            'is_primary' => false,
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function forCompany(CompanyModel $company): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $company->id,
        ]);
    }
}
