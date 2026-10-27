<?php

namespace App\Identity\Domain\Enums;

enum RoleName: string
{
    case SuperAdmin = 'super-admin';
    case CompanyAdmin = 'company-admin';
    case ServiceManager = 'service-manager';
    case Engineer = 'engineer';
    case Technician = 'technician';
    case Auditor = 'auditor';
    case ReadOnly = 'read-only';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Superadministrador',
            self::CompanyAdmin => 'Administrador de empresa',
            self::ServiceManager => 'Jefe de servicio',
            self::Engineer => 'Ingeniero',
            self::Technician => 'Técnico',
            self::Auditor => 'Auditor',
            self::ReadOnly => 'Solo lectura',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Control global de empresas, usuarios y configuración.',
            self::CompanyAdmin => 'Administra usuarios, departamentos y operación de su empresa.',
            self::ServiceManager => 'Coordina servicios, equipos y operación de la empresa.',
            self::Engineer => 'Atiende y resuelve servicios técnicos especializados.',
            self::Technician => 'Ejecuta soporte y mantenimientos asignados.',
            self::Auditor => 'Consulta información y auditoría sin modificar registros.',
            self::ReadOnly => 'Consulta información operativa sin modificarla.',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $role) => $role->value, self::cases());
    }

    /**
     * @return array<int, self>
     */
    public static function companyAssignable(): array
    {
        return [
            self::ServiceManager,
            self::Engineer,
            self::Technician,
            self::Auditor,
            self::ReadOnly,
        ];
    }

    public function isPrivileged(): bool
    {
        return in_array($this, [self::SuperAdmin, self::CompanyAdmin], true);
    }
}
