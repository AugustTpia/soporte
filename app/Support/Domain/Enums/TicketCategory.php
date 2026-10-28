<?php

namespace App\Support\Domain\Enums;

enum TicketCategory: string
{
    case Hardware = 'hardware';
    case Connectivity = 'connectivity';
    case SoftwareInstallation = 'software_installation';
    case Updates = 'updates';
    case Configuration = 'configuration';
    case Backups = 'backups';
    case VideoSurveillance = 'video_surveillance';
    case Nextcloud = 'nextcloud';
    case BusinessSystems = 'business_systems';
    case Administrative = 'administrative';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Hardware => 'Hardware',
            self::Connectivity => 'Conectividad',
            self::SoftwareInstallation => 'Instalación de software',
            self::Updates => 'Actualizaciones',
            self::Configuration => 'Configuración',
            self::Backups => 'Respaldos',
            self::VideoSurveillance => 'Videovigilancia',
            self::Nextcloud => 'Nextcloud',
            self::BusinessSystems => 'Sistemas empresariales',
            self::Administrative => 'Gestión administrativa',
            self::Other => 'Otro',
        };
    }
}
