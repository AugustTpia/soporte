<?php

namespace Database\Factories;

use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use App\Identity\Infrastructure\Persistence\Eloquent\User;
use App\Support\Domain\Enums\TicketCategory;
use App\Support\Domain\Enums\TicketPriority;
use App\Support\Domain\Enums\TicketStatus;
use App\Support\Infrastructure\Persistence\Eloquent\Models\SupportTicketModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportTicketModel>
 */
class SupportTicketFactory extends Factory
{
    protected $model = SupportTicketModel::class;

    public function definition(): array
    {
        return [
            'company_id' => CompanyModel::factory(),
            'folio' => 'TCK-'.fake()->unique()->numerify('######'),
            'location_id' => null,
            'asset_id' => null,
            'service_account_id' => null,
            'category' => fake()->randomElement(TicketCategory::cases())->value,
            'subject' => fake()->sentence(6),
            'description' => fake()->paragraph(),
            'requester_name' => fake()->name(),
            'requester_contact' => fake()->phoneNumber(),
            'priority' => fake()->randomElement(TicketPriority::cases())->value,
            'status' => TicketStatus::Open->value,
            'opened_at' => now(),
            'due_at' => null,
            'resolved_at' => null,
            'closed_at' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'created_by' => null,
        ];
    }

    public function forCompany(CompanyModel $company, ?User $creator = null): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $company->id,
            'created_by' => $creator?->id,
        ]);
    }
}
