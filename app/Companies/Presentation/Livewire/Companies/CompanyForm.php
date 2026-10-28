<?php

namespace App\Companies\Presentation\Livewire\Companies;

use App\Companies\Application\DTOs\CompanyInput;
use App\Companies\Application\UseCases\ChangeCompanyStatus;
use App\Companies\Application\UseCases\CreateCompany;
use App\Companies\Application\UseCases\UpdateCompany;
use App\Companies\Domain\Enums\CompanyFeature;
use App\Companies\Domain\Enums\CompanyStatus;
use App\Companies\Domain\Repositories\CompanyRepository;
use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use App\Shared\Application\Contracts\TenantContext;
use App\Shared\Domain\Exceptions\DomainException;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Empresa')]
class CompanyForm extends Component
{
    public ?int $companyId = null;

    public string $name = '';

    public string $legalName = '';

    public string $taxId = '';

    public string $email = '';

    public string $phone = '';

    public string $website = '';

    public string $industry = '';

    public string $timezone = 'America/Mexico_City';

    public string $locale = 'es_MX';

    public string $status = 'active';

    public bool $busTicketCancellations = false;

    public function mount(?CompanyModel $company = null): void
    {
        if ($company === null) {
            $this->authorize('create', CompanyModel::class);

            return;
        }

        $this->authorize('update', $company);

        $domain = app(CompanyRepository::class)->find($company->id);

        if ($domain === null) {
            abort(404);
        }

        $this->companyId = $domain->id;
        $this->name = $domain->name;
        $this->legalName = $domain->legalName ?? '';
        $this->taxId = $domain->taxId ?? '';
        $this->email = $domain->email ?? '';
        $this->phone = $domain->phone ?? '';
        $this->website = $domain->website ?? '';
        $this->industry = $domain->industry ?? '';
        $this->timezone = $domain->timezone;
        $this->locale = $domain->locale;
        $this->status = $domain->status->value;
        $this->busTicketCancellations = $domain->hasFeature(CompanyFeature::BusTicketCancellations);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:160'],
            'legalName' => ['nullable', 'string', 'max:200'],
            'taxId' => ['nullable', 'string', 'max:40', Rule::unique('companies', 'tax_id')->ignore($this->companyId)],
            'email' => ['nullable', 'email', 'max:160', Rule::unique('companies', 'email')->ignore($this->companyId)],
            'phone' => ['nullable', 'string', 'max:30'],
            'website' => ['nullable', 'url', 'max:190'],
            'industry' => ['nullable', 'string', 'max:120'],
            'timezone' => ['required', 'timezone'],
            'locale' => ['required', 'string', 'max:10'],
            'busTicketCancellations' => ['boolean'],
        ]);

        $settings = null;

        if ($this->companyId !== null) {
            $existing = app(CompanyRepository::class)->find($this->companyId);

            if ($existing === null) {
                abort(404);
            }

            $settings = $existing->settingsWithFeature(
                CompanyFeature::BusTicketCancellations,
                $this->busTicketCancellations,
            );
        }

        $input = new CompanyInput(
            name: $validated['name'],
            legalName: $this->clean($validated['legalName'] ?? null),
            taxId: $this->clean($validated['taxId'] ?? null),
            email: $this->clean($validated['email'] ?? null),
            phone: $this->clean($validated['phone'] ?? null),
            website: $this->clean($validated['website'] ?? null),
            industry: $this->clean($validated['industry'] ?? null),
            timezone: $validated['timezone'],
            locale: $validated['locale'],
            settings: $settings,
        );

        try {
            if ($this->companyId === null) {
                $company = app(CreateCompany::class)->execute($input, (int) auth()->id());

                app(TenantContext::class)->setCompanyId($company->id);

                Flux::toast(variant: 'success', text: 'Empresa creada correctamente.');

                $this->redirectRoute('dashboard', navigate: true);

                return;
            }

            app(UpdateCompany::class)->execute($this->companyId, $input);

            Flux::toast(variant: 'success', text: 'Empresa actualizada correctamente.');

            $this->redirectRoute('companies.index', navigate: true);
        } catch (DomainException $exception) {
            $this->addError('form', $exception->getMessage());
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }
    }

    public function toggleStatus(): void
    {
        if ($this->companyId === null) {
            return;
        }

        $this->authorize('update', CompanyModel::query()->findOrFail($this->companyId));

        $company = app(CompanyRepository::class)->find($this->companyId);

        if ($company === null) {
            abort(404);
        }

        try {
            $newStatus = $company->isActive() ? CompanyStatus::Inactive : CompanyStatus::Active;

            app(ChangeCompanyStatus::class)->execute($this->companyId, $newStatus);

            if ($newStatus === CompanyStatus::Inactive && app(TenantContext::class)->companyId() === $this->companyId) {
                app(TenantContext::class)->clear();
            }

            Flux::toast(variant: 'success', text: 'Estado de la empresa actualizado.');
            $this->redirectRoute('companies.index', navigate: true);
        } catch (DomainException $exception) {
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }
    }

    public function render(): View
    {
        return view('companies.livewire.companies.form', [
            'isEditing' => $this->companyId !== null,
        ]);
    }

    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
