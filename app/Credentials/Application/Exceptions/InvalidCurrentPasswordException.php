<?php

namespace App\Credentials\Application\Exceptions;

use RuntimeException;

final class InvalidCurrentPasswordException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('La contraseña actual no es correcta.');
    }
}
