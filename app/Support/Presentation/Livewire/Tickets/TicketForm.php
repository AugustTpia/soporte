<?php

namespace App\Support\Presentation\Livewire\Tickets;

use App\Accounts\Domain\Repositories\ServiceAccountRepository;
use App\Assets\Domain\Repositories\AssetRepository;
use App\Companies\Domain\Repositories\CompanyRepository;
use App\Locations\Domain\Repositories\LocationRepository;
use App\Shared\Application\Contracts\TenantContext;
use App\Shared\Domain\Exceptions\DomainException;
use App\Support\Application\DTOs\TicketInput;
use App\Support\Application\UseCases\CreateTicket;
use App\Support\Application\UseCases\UpdateTicket;
use App\Support\Domain\Enums\TicketCategory;
use App\Support\Domain\Enums\TicketPriority;
use App\Support\Domain\Repositories\TicketRepository;
use App\Support\Infrastructure\Persistence\Eloquent\Models\SupportTicketModel;
use DateTimeImmutable;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Ticket')]
class TicketForm extends Component
{
    public ?int $ticketId = null;

    public ?int $companyId = null;

    public string $companyName = '';

    public string $category = 'hardware';

    public string $subject = '';

    public string $description = '';

    public string $priority = 'medium';

    public string $requesterName = '';

    public string $requesterContact = '';

    public string $locationId = '';

    public string $assetId = '';

    public string $serviceAccountId = '';

    public string $dueAt = '';

    /** @var array<int, array{id: int, name: string}> */
    public array $locationOptions = [];

    /** @var array<int, array{id: int, label: string}> */
    public array $assetOptions = [];

    /** @var array<int, array{id: int, label: string}> */
    public array $accountOptions = [];

    /** @var array<string, string> */
    public array $categoryOptions = [];

    /** @var array<string, string> */
    public array $priorityOptions = [];

    public function mount(?SupportTicketModel $ticket = null): void
    {
        $this->companyId = app(TenantContext::class)->companyId();

        if ($this->companyId === null) {
            $this->redirectRoute('companies.index', navigate: true);

            return;
        }

        $company = app(CompanyRepository::class)->find($this->companyId);
        $actorCompany = auth()->user()->company;

        $this->companyName = app(TenantContext::class)->isSuperAdmin()
            ? ($company !== null ? $company->name : '')
            : ($actorCompany !== null ? $actorCompany->name : '');

        if ($ticket === null) {
            $this->authorize('create', SupportTicketModel::class);
            $this->loadOptions();
            $this->loadEnums();

            return;
        }

        $this->authorize('update', $ticket);

        $domain = app(TicketRepository::class)->find($ticket->id, $this->companyId);

        if ($domain === null) {
            abort(404);
        }

        $this->ticketId = $domain->id;
        $this->category = $domain->category->value;
        $this->subject = $domain->subject;
        $this->description = $domain->description;
        $this->priority = $domain->priority->value;
        $this->requesterName = $domain->requesterName ?? '';
        $this->requesterContact = $domain->requesterContact ?? '';
        $this->locationId = $domain->locationId === null ? '' : (string) $domain->locationId;
        $this->assetId = $domain->assetId === null ? '' : (string) $domain->assetId;
        $this->serviceAccountId = $domain->serviceAccountId === null ? '' : (string) $domain->serviceAccountId;
        $this->dueAt = $domain->dueAt?->format('Y-m-d\TH:i') ?? '';

        $this->loadOptions();
        $this->loadEnums();
    }

    public function save(): void
    {
        if ($this->companyId === null) {
            abort(403);
        }

        $validated = $this->validate([
            'category' => ['required', Rule::enum(TicketCategory::class)],
            'subject' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:10000'],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
            'requesterName' => ['nullable', 'string', 'max:150'],
            'requesterContact' => ['nullable', 'string', 'max:150'],
            'locationId' => [
                'nullable',
                'integer',
                Rule::exists('locations', 'id')->where('company_id', $this->companyId),
            ],
            'assetId' => [
                'nullable',
                'integer',
                Rule::exists('assets', 'id')->where('company_id', $this->companyId),
            ],
            'serviceAccountId' => [
                'nullable',
                'integer',
                Rule::exists('service_accounts', 'id')->where('company_id', $this->companyId),
            ],
            'dueAt' => ['nullable', 'date_format:Y-m-d\TH:i'],
        ]);

        $input = new TicketInput(
            category: TicketCategory::from($validated['category']),
            subject: $validated['subject'],
            description: $validated['description'],
            priority: TicketPriority::from($validated['priority']),
            requesterName: $this->clean($validated['requesterName'] ?? null),
            requesterContact: $this->clean($validated['requesterContact'] ?? null),
            locationId: $this->integerOrNull($validated['locationId'] ?? null),
            assetId: $this->integerOrNull($validated['assetId'] ?? null),
            serviceAccountId: $this->integerOrNull($validated['serviceAccountId'] ?? null),
            dueAt: $this->dateTimeOrNull($validated['dueAt'] ?? null),
        );

        try {
            if ($this->ticketId === null) {
                $ticket = app(CreateTicket::class)->execute($this->companyId, $input, (int) auth()->id());
                Flux::toast(variant: 'success', text: 'Ticket creado correctamente.');

                $this->redirectRoute('tickets.show', $ticket->id, navigate: true);

                return;
            }

            app(UpdateTicket::class)->execute(
                companyId: $this->companyId,
                ticketId: $this->ticketId,
                input: $input,
                actorId: (int) auth()->id(),
            );

            Flux::toast(variant: 'success', text: 'Ticket actualizado correctamente.');
            $this->redirectRoute('tickets.show', $this->ticketId, navigate: true);
        } catch (DomainException $exception) {
            $this->addError('form', $exception->getMessage());
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }
    }

    public function render(): View
    {
        return view('tickets.livewire.form', [
            'isEditing' => $this->ticketId !== null,
        ]);
    }

    private function loadOptions(): void
    {
        if ($this->companyId === null) {
            return;
        }

        $this->locationOptions = collect(app(LocationRepository::class)->forCompany($this->companyId))
            ->map(fn ($location) => [
                'id' => $location->id,
                'name' => $location->name,
            ])
            ->all();

        $this->assetOptions = collect(app(AssetRepository::class)->forCompany($this->companyId))
            ->map(fn ($asset) => [
                'id' => $asset->id,
                'label' => $asset->internalCode.($asset->hostname !== null ? ' · '.$asset->hostname : ''),
            ])
            ->all();

        $this->accountOptions = collect(app(ServiceAccountRepository::class)->forCompany($this->companyId))
            ->map(fn ($account) => [
                'id' => $account->id,
                'label' => $account->username.' · '.$account->instance,
            ])
            ->all();
    }

    private function loadEnums(): void
    {
        $this->categoryOptions = collect(TicketCategory::cases())
            ->mapWithKeys(fn (TicketCategory $category) => [$category->value => $category->label()])
            ->all();

        $this->priorityOptions = collect(TicketPriority::cases())
            ->mapWithKeys(fn (TicketPriority $priority) => [$priority->value => $priority->label()])
            ->all();
    }

    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function integerOrNull(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    private function dateTimeOrNull(?string $value): ?DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value);

        return $date === false ? null : $date;
    }
}
