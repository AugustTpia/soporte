<?php

namespace Database\Factories;

use App\BusTicketCancellations\Domain\Enums\BusTicketCancellationResult;
use App\BusTicketCancellations\Infrastructure\Persistence\Eloquent\Models\BusTicketCancellationModel;
use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use App\Identity\Infrastructure\Persistence\Eloquent\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusTicketCancellationModel>
 */
class BusTicketCancellationFactory extends Factory
{
    protected $model = BusTicketCancellationModel::class;

    public function definition(): array
    {
        return [
            'company_id' => CompanyModel::factory(),
            'support_ticket_id' => null,
            'ticket_number' => 'BOL-'.fake()->unique()->numerify('######'),
            'requested_at' => now()->subHour(),
            'executed_at' => now(),
            'original_ticket_at' => now()->addDay(),
            'amount_cents' => fake()->numberBetween(5000, 250000),
            'operator_id' => User::factory(),
            'result' => BusTicketCancellationResult::Cancelled->value,
            'reason' => fake()->sentence(),
            'authorization_reference' => fake()->bothify('AUT-####'),
            'evidence_notes' => null,
            'created_by' => null,
        ];
    }

    public function forCompany(CompanyModel $company, User $operator, ?User $creator = null): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $company->id,
            'operator_id' => $operator->id,
            'created_by' => $creator === null ? $operator->id : $creator->id,
        ]);
    }
}
