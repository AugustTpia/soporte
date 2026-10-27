<?php

namespace Database\Factories;

use App\Companies\Domain\Enums\CompanyStatus;
use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CompanyModel>
 */
class CompanyFactory extends Factory
{
    protected $model = CompanyModel::class;

    public function definition(): array
    {
        return [
            'public_id' => (string) Str::ulid(),
            'name' => fake()->company(),
            'legal_name' => fake()->company().' S.A. de C.V.',
            'tax_id' => strtoupper(fake()->bothify('???######???')),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->numerify('55########'),
            'website' => fake()->url(),
            'industry' => fake()->randomElement(['Manufactura', 'Salud', 'Retail', 'Servicios']),
            'status' => CompanyStatus::Active->value,
            'timezone' => 'America/Mexico_City',
            'locale' => 'es_MX',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CompanyStatus::Inactive->value,
        ]);
    }
}
