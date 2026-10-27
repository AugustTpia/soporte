<?php

namespace Database\Factories;

use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use App\Locations\Infrastructure\Persistence\Eloquent\Models\LocationModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LocationModel>
 */
class LocationFactory extends Factory
{
    protected $model = LocationModel::class;

    public function definition(): array
    {
        $name = 'Sucursal '.fake()->unique()->numerify('####');

        return [
            'company_id' => CompanyModel::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'street' => fake()->streetName(),
            'exterior_number' => fake()->numerify('###'),
            'interior_number' => fake()->optional()->numerify('##'),
            'neighborhood' => fake()->citySuffix(),
            'city' => fake()->city(),
            'state' => fake()->randomElement(['CDMX', 'Jalisco', 'Nuevo León', 'Puebla', 'Querétaro']),
            'postal_code' => fake()->numerify('#####'),
            'country' => 'México',
            'phone' => fake()->numerify('55########'),
            'notes' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
