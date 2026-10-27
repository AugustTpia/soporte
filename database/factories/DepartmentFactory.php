<?php

namespace Database\Factories;

use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use App\Departments\Infrastructure\Persistence\Eloquent\Models\DepartmentModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DepartmentModel>
 */
class DepartmentFactory extends Factory
{
    protected $model = DepartmentModel::class;

    public function definition(): array
    {
        $name = fake()->unique()->jobTitle();

        return [
            'company_id' => CompanyModel::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
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
