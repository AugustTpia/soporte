<?php

namespace App\Contacts\Presentation\Livewire;

use App\Contacts\Application\DTOs\ClientContactInput;
use App\Contacts\Application\UseCases\ChangeClientContactStatus;
use App\Contacts\Application\UseCases\CreateClientContact;
use App\Contacts\Application\UseCases\UpdateClientContact;
use App\Contacts\Domain\Entities\ClientContact;
use App\Contacts\Domain\Repositories\ClientContactRepository;
use App\Locations\Domain\Repositories\LocationRepository;
use App\Shared\Application\Contracts\TenantContext;
use App\Shared\Domain\Exceptions\DomainException;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CompanyContacts extends Component
{
    public int $companyId;

    public ?int $contactId = null;

    public string $name = '';

    public string $locationId = '';

    public string $position = '';

    public string $email = '';

    public string $phone = '';

    public string $whatsapp = '';

    public bool $isPrimary = false;

    public bool $isActive = true;

    public string $notes = '';

    public string $search = '';

    public string $statusFilter = '';

    public function mount(int $companyId): void
    {
        Gate::authorize('contacts.view');

        $tenantCompanyId = app(TenantContext::class)->companyId();

        if ($tenantCompanyId === null || $tenantCompanyId !== $companyId) {
            abort(404);
        }

        $this->companyId = $companyId;
    }

    public function updatedIsActive(bool $active): void
    {
        if (! $active) {
            $this->isPrimary = false;
        }
    }

    public function updatedIsPrimary(bool $primary): void
    {
        if ($primary) {
            $this->isActive = true;
        }
    }

    public function openCreate(): void
    {
        Gate::authorize('contacts.manage');

        $this->resetForm();
        Flux::modal('contact-form')->show();
    }

    public function openEdit(int $contactId): void
    {
        Gate::authorize('contacts.manage');

        $contact = $this->findContact($contactId);

        $this->contactId = $contact->id;
        $this->name = $contact->name;
        $this->locationId = $contact->locationId === null ? '' : (string) $contact->locationId;
        $this->position = $contact->position ?? '';
        $this->email = $contact->email ?? '';
        $this->phone = $contact->phone ?? '';
        $this->whatsapp = $contact->whatsapp ?? '';
        $this->isPrimary = $contact->isPrimary;
        $this->isActive = $contact->isActive;
        $this->notes = $contact->notes ?? '';

        Flux::modal('contact-form')->show();
    }

    public function closeForm(): void
    {
        Flux::modal('contact-form')->close();
        $this->resetForm();
    }

    public function save(): void
    {
        Gate::authorize('contacts.manage');

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:150'],
            'locationId' => [
                'nullable',
                'integer',
                Rule::exists('locations', 'id')->where('company_id', $this->companyId),
            ],
            'position' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'isPrimary' => ['boolean'],
            'isActive' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $input = new ClientContactInput(
            name: $validated['name'],
            locationId: $this->integerOrNull($validated['locationId'] ?? null),
            position: $this->clean($validated['position'] ?? null),
            email: $this->clean($validated['email'] ?? null),
            phone: $this->clean($validated['phone'] ?? null),
            whatsapp: $this->clean($validated['whatsapp'] ?? null),
            isPrimary: (bool) $validated['isPrimary'],
            notes: $this->clean($validated['notes'] ?? null),
            isActive: (bool) $validated['isActive'],
        );

        try {
            if ($this->contactId === null) {
                app(CreateClientContact::class)->execute($this->companyId, $input);
                Flux::toast(variant: 'success', text: 'Contacto creado correctamente.');
            } else {
                app(UpdateClientContact::class)->execute($this->companyId, $this->contactId, $input);
                Flux::toast(variant: 'success', text: 'Contacto actualizado correctamente.');
            }

            Flux::modal('contact-form')->close();
            $this->resetForm();
        } catch (DomainException $exception) {
            $this->addError('form', $exception->getMessage());
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }
    }

    public function toggleStatus(int $contactId): void
    {
        Gate::authorize('contacts.manage');

        $contact = $this->findContact($contactId);

        try {
            app(ChangeClientContactStatus::class)->execute(
                companyId: $this->companyId,
                contactId: $contactId,
                active: ! $contact->isActive,
            );

            Flux::toast(
                variant: 'success',
                text: $contact->isActive ? 'Contacto desactivado.' : 'Contacto activado.',
            );
        } catch (DomainException $exception) {
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }
    }

    public function render(): View
    {
        Gate::authorize('contacts.view');

        $tenantCompanyId = app(TenantContext::class)->companyId();

        if ($tenantCompanyId === null || $tenantCompanyId !== $this->companyId) {
            abort(404);
        }

        $term = mb_strtolower(trim($this->search));

        $contacts = collect(app(ClientContactRepository::class)->forCompany($this->companyId))
            ->filter(fn (ClientContact $contact) => $term === ''
                || str_contains(mb_strtolower($contact->name), $term)
                || str_contains(mb_strtolower((string) $contact->email), $term)
                || str_contains(mb_strtolower((string) $contact->phone), $term)
                || str_contains(mb_strtolower((string) $contact->position), $term))
            ->when($this->statusFilter === 'active', fn ($contacts) => $contacts->where('isActive', true))
            ->when($this->statusFilter === 'inactive', fn ($contacts) => $contacts->where('isActive', false))
            ->values();

        return view('contacts.livewire.company-contacts', [
            'contacts' => $contacts,
            'locations' => app(LocationRepository::class)->forCompany($this->companyId),
            'canManage' => Gate::allows('contacts.manage'),
        ]);
    }

    private function findContact(int $contactId): ClientContact
    {
        return app(ClientContactRepository::class)->find($contactId, $this->companyId)
            ?? abort(404);
    }

    public function resetForm(): void
    {
        $this->reset(
            'contactId',
            'name',
            'locationId',
            'position',
            'email',
            'phone',
            'whatsapp',
            'isPrimary',
            'isActive',
            'notes',
        );

        $this->isActive = true;
        $this->resetErrorBag();
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
}
