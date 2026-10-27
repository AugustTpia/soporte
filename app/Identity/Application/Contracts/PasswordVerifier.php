<?php

namespace App\Identity\Application\Contracts;

interface PasswordVerifier
{
    public function verify(int $userId, string $plainPassword): bool;
}
