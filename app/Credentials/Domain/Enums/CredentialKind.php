<?php

namespace App\Credentials\Domain\Enums;

enum CredentialKind: string
{
    case Password = 'password';
    case AppPassword = 'app_password';
    case Token = 'token';
    case RecoveryCodes = 'recovery_codes';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Password => 'Contraseña',
            self::AppPassword => 'Contraseña de aplicación',
            self::Token => 'Token',
            self::RecoveryCodes => 'Códigos de recuperación',
            self::Other => 'Otro secreto',
        };
    }
}
