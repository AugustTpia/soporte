<?php

namespace App\Locations\Presentation\Livewire;

use App\Companies\Domain\Repositories\CompanyRepository;
use App\Locations\Application\DTOs\LocationInput;
use App\Locations\Application\UseCases\CreateLocation;
use App\Locations\Application\UseCases\UpdateLocation;
use App\Locations\Domain\Repositories\LocationRepository;
use App\Locations\Infrastructure\Persistence\Eloquent\Models\LocationModel;
use App\Shared\Application\Contracts\TenantContext;
use App\Shared\Domain\Exceptions\DomainException;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Ubicación')]
class LocationForm extends Component
{
    public ?int $locationId = null;

    public ?int $companyId = null;

    public string $companyName = '';

    public string $name = '';

    public string $street = '';

    public string $exteriorNumber = '';

    public string $interiorNumber = '';

    public string $neighborhood = '';

    public string $city = '';

    public string $state = '';

    public string $postalCode = '';

    public string $country = 'México';

    public string $phone = '';

    public string $notes = '';

    public bool $isActive = true;

    public function mount(?LocationModel $location = null): void
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

        if ($location === null) {
            $this->authorize('create', LocationModel::class);

            return;
        }

        $this->authorize('update', $location);

        $domain = app(LocationRepository::class)->find($location->id, $this->companyId);

        if ($domain === null) {
            abort(404);
        }

        $this->locationId = $domain->id;
        $this->name = $domain->name;
        $this->street = $domain->street;
        $this->exteriorNumber = $domain->exteriorNumber;
        $this->interiorNumber = $domain->interiorNumber ?? '';
        $this->neighborhood = $domain->neighborhood ?? '';
        $this->city = $domain->city;
        $this->state = $domain->state ?? '';
        $this->postalCode = $domain->postalCode ?? '';
        $this->country = $domain->country;
        $this->phone = $domain->phone ?? '';
        $this->notes = $domain->notes ?? '';
        $this->isActive = $domain->isActive;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'street' => ['required', 'string', 'max:180'],
            'exteriorNumber' => ['required', 'string', 'max:20'],
            'interiorNumber' => ['nullable', 'string', 'max:20'],
            'neighborhood' => ['nullable', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'postalCode' => ['nullable', 'string', 'max:12'],
            'country' => ['required', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'isActive' => ['boolean'],
        ]);

        if ($this->companyId === null) {
            abort(403);
        }

        $input = new LocationInput(
            name: $validated['name'],
            street: $validated['street'],
            exteriorNumber: $validated['exteriorNumber'],
            city: $validated['city'],
            country: $validated['country'],
            interiorNumber: $this->clean($validated['interiorNumber'] ?? null),
            neighborhood: $this->clean($validated['neighborhood'] ?? null),
            state: $this->clean($validated['state'] ?? null),
            postalCode: $this->clean($validated['postalCode'] ?? null),
            phone: $this->clean($validated['phone'] ?? null),
            notes: $this->clean($validated['notes'] ?? null),
            isActive: (bool) $validated['isActive'],
        );

        try {
            if ($this->locationId === null) {
                app(CreateLocation::class)->execute($this->companyId, $input);
                Flux::toast(variant: 'success', text: 'Ubicación creada correctamente.');
            } else {
                app(UpdateLocation::class)->execute($this->companyId, $this->locationId, $input);
                Flux::toast(variant: 'success', text: 'Ubicación actualizada correctamente.');
            }

            $this->redirectRoute('locations.index', navigate: true);
        } catch (DomainException $exception) {
            $this->addError('form', $exception->getMessage());
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }
    }

    public function render(): View
    {
        return view('locations.livewire.form', [
            'isEditing' => $this->locationId !== null,
        ]);
    }

    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
