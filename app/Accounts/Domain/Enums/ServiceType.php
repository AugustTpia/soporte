<?php

namespace App\Accounts\Domain\Enums;

enum ServiceType: string
{
    case Nextcloud = 'nextcloud';
    case Windows = 'windows';
    case Email = 'email';
    case Server = 'server';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Nextcloud => 'Nextcloud',
            self::Windows => 'Windows',
            self::Email => 'Correo empresarial',
            self::Server => 'Servidor',
            self::Other => 'Otro servicio',
        };
    }
}
