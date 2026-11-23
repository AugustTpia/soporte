<?php

namespace App\Assistant;

use App\Companies\Domain\Repositories\CompanyRepository;
use App\Shared\Application\Contracts\TenantContext;

/**
 * Interruptor del asistente IA por empresa.
 *
 * El ajuste vive en companies.settings -> features -> assistant
 * (convención existente de CompanyFeature). Reglas:
 * - Sin empresa activa (superadmin en vista global): el asistente
 *   sigue disponible, como hasta ahora.
 * - Empresa con el ajuste sin guardar (creada antes del interruptor):
 *   activado por defecto, para no romper lo actual.
 * - Empresa con features.assistant = false: el asistente no aparece
 *   (ni botón flotante ni entrada del menú) y no responde.
 */
final readonly class AssistantAvailability
{
    public function __construct(
        private TenantContext $tenant,
        private CompanyRepository $companies,
    ) {}

    public function enabled(): bool
    {
        $companyId = $this->tenant->companyId();

        if ($companyId === null) {
            return true;
        }

        $company = $this->companies->find($companyId);

        return $company?->assistantEnabled() ?? true;
    }

    public function disabledMessage(): string
    {
        return 'El asistente IA no está activado para esta empresa. Pide al superadministrador que lo active en la ficha de la empresa.';
    }
}
