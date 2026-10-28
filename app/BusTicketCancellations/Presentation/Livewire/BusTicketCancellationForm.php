<?php

namespace App\BusTicketCancellations\Presentation\Livewire;

use App\BusTicketCancellations\Application\DTOs\BusTicketCancellationInput;
use App\BusTicketCancellations\Application\UseCases\CreateBusTicketCancellation;
use App\BusTicketCancellations\Application\UseCases\UpdateBusTicketCancellation;
use App\BusTicketCancellations\Domain\Enums\BusTicketCancellationResult;
use App\BusTicketCancellations\Domain\Repositories\BusTicketCancellationRepository;
use App\BusTicketCancellations\Infrastructure\Persistence\Eloquent\Models\BusTicketCancellationModel;
use App\Companies\Domain\Repositories\CompanyRepository;
use App\Identity\Domain\Enums\RoleName;
use App\Identity\Domain\Repositories\UserRepository;
use App\Shared\Application\Contracts\TenantContext;
use App\Shared\Domain\Exceptions\DomainException;
use App\Support\Domain\Repositories\TicketRepository;
use DateTimeImmutable;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Cancelación de boleto')]
class BusTicketCancellationForm extends Component
{
    public ?int $cancellationId = null;

    public ?int $companyId = null;

    public string $companyName = '';

    public string $ticketNumber = '';

    public string $supportTicketId = '';

    public string $requestedAt = '';

    public string $executedAt = '';

    public string $originalTicketAt = '';

    public string $amount = '';

    public string $operatorId = '';

    public string $result = 'cancelled';

    public string $reason = '';

    public string $authorizationReference = '';

    public string $evidenceNotes = '';

    /** @var array<int, array{id: int, label: string}> */
    public array $ticketOptions = [];

    /** @var array<int, array{id: int, name: string}> */
    public array $operatorOptions = [];

    /** @var array<string, string> */
    public array $resultOptions = [];

    public function mount(?BusTicketCancellationModel $cancellation = null): void
    {
        $this->companyId = app(TenantContext::class)->companyId();

        if ($this->companyId === null) {
            $this->redirectRoute('companies.index', navigate: true);

            return;
        }

        $company = app(CompanyRepository::class)->find($this->companyId);

        if ($company === null) {
            abort(404);
        }

        $this->companyName = $company->name;

        if ($cancellation === null) {
            $this->authorize('create', BusTicketCancellationModel::class);
            $this->requestedAt = now()->format('Y-m-d\TH:i');
            $this->executedAt = now()->format('Y-m-d\TH:i');
            $this->operatorId = (string) auth()->id();
            $this->loadOptions();
            $this->loadEnums();

            return;
        }

        $this->authorize('update', $cancellation);

        $domain = app(BusTicketCancellationRepository::class)->find($cancellation->id, $this->companyId);

        if ($domain === null) {
            abort(404);
        }

        $this->cancellationId = $domain->id;
        $this->ticketNumber = $domain->ticketNumber;
        $this->supportTicketId = $domain->supportTicketId === null ? '' : (string) $domain->supportTicketId;
        $this->requestedAt = $domain->requestedAt->format('Y-m-d\TH:i');
        $this->executedAt = $domain->executedAt?->format('Y-m-d\TH:i') ?? '';
        $this->originalTicketAt = $domain->originalTicketAt?->format('Y-m-d\TH:i') ?? '';
        $this->amount = $domain->amountCents === null ? '' : number_format($domain->amountCents / 100, 2, '.', '');
        $this->operatorId = (string) $domain->operatorId;
        $this->result = $domain->result->value;
        $this->reason = $domain->reason;
        $this->authorizationReference = $domain->authorizationReference ?? '';
        $this->evidenceNotes = $domain->evidenceNotes ?? '';

        $this->loadOptions();
        $this->loadEnums();
    }

    public function updatedResult(string $value): void
    {
        if ($value === BusTicketCancellationResult::Pending->value) {
            $this->executedAt = '';

            return;
        }

        if ($this->executedAt === '') {
            $this->executedAt = now()->format('Y-m-d\TH:i');
        }
    }

    public function save(): void
    {
        if ($this->companyId === null) {
            abort(403);
        }

        $validated = $this->validate([
            'ticketNumber' => ['required', 'string', 'max:100'],
            'supportTicketId' => [
                'nullable',
                'integer',
                Rule::exists('support_tickets', 'id')->where('company_id', $this->companyId),
            ],
            'requestedAt' => ['required', 'date_format:Y-m-d\TH:i'],
            'executedAt' => ['nullable', 'date_format:Y-m-d\TH:i', 'after_or_equal:requestedAt'],
            'originalTicketAt' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'operatorId' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('company_id', $this->companyId),
            ],
            'result' => ['required', Rule::enum(BusTicketCancellationResult::class)],
            'reason' => ['required', 'string', 'max:2000'],
            'authorizationReference' => ['nullable', 'string', 'max:150'],
            'evidenceNotes' => ['nullable', 'string', 'max:2000'],
        ]);

        $input = new BusTicketCancellationInput(
            supportTicketId: $this->integerOrNull($validated['supportTicketId'] ?? null),
            ticketNumber: $validated['ticketNumber'],
            requestedAt: $this->dateTime($validated['requestedAt']),
            executedAt: $this->nullableDateTime($validated['executedAt'] ?? null),
            originalTicketAt: $this->nullableDateTime($validated['originalTicketAt'] ?? null),
            amountCents: $this->amountToCents($validated['amount'] ?? null),
            operatorId: (int) $validated['operatorId'],
            result: BusTicketCancellationResult::from($validated['result']),
            reason: $validated['reason'],
            authorizationReference: $this->clean($validated['authorizationReference'] ?? null),
            evidenceNotes: $this->clean($validated['evidenceNotes'] ?? null),
        );

        try {
            if ($this->cancellationId === null) {
                app(CreateBusTicketCancellation::class)->execute(
                    companyId: $this->companyId,
                    input: $input,
                    actorId: (int) auth()->id(),
                );

                Flux::toast(variant: 'success', text: 'Cancelación registrada correctamente.');
                $this->redirectRoute('bus-ticket-cancellations.index', navigate: true);

                return;
            }

            app(UpdateBusTicketCancellation::class)->execute(
                companyId: $this->companyId,
                cancellationId: $this->cancellationId,
                input: $input,
                actorId: (int) auth()->id(),
            );

            Flux::toast(variant: 'success', text: 'Cancelación actualizada correctamente.');
            $this->redirectRoute('bus-ticket-cancellations.index', navigate: true);
        } catch (DomainException $exception) {
            $this->addError('form', $exception->getMessage());
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }
    }

    public function render(): View
    {
        return view('bus-ticket-cancellations.livewire.form', [
            'isEditing' => $this->cancellationId !== null,
        ]);
    }

    private function loadOptions(): void
    {
        if ($this->companyId === null) {
            return;
        }

        $this->ticketOptions = collect(app(TicketRepository::class)->forCompany($this->companyId))
            ->map(fn ($ticket) => [
                'id' => $ticket->id,
                'label' => $ticket->folio.' · '.$ticket->subject,
            ])
            ->all();

        $operatorRoles = [
            RoleName::CompanyAdmin->value,
            RoleName::ServiceManager->value,
            RoleName::Engineer->value,
            RoleName::Technician->value,
        ];

        $this->operatorOptions = collect(app(UserRepository::class)->forCompany($this->companyId))
            ->filter(fn ($user) => $user->isActive() && array_intersect($user->roles, $operatorRoles) !== [])
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
            ])
            ->values()
            ->all();
    }

    private function loadEnums(): void
    {
        $this->resultOptions = collect(BusTicketCancellationResult::cases())
            ->mapWithKeys(fn (BusTicketCancellationResult $result) => [$result->value => $result->label()])
            ->all();
    }

    private function integerOrNull(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    private function amountToCents(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) round(((float) $value) * 100);
    }

    private function dateTime(string $value): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value);

        return $date === false ? new DateTimeImmutable : $date;
    }

    private function nullableDateTime(?string $value): ?DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value);

        return $date === false ? null : $date;
    }

    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
