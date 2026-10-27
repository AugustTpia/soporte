<?php

namespace App\Identity\Infrastructure\Security;

use App\Identity\Application\Contracts\PasswordVerifier;
use App\Identity\Infrastructure\Persistence\Eloquent\User;
use Illuminate\Support\Facades\Hash;

final class LaravelPasswordVerifier implements PasswordVerifier
{
    public function verify(int $userId, string $plainPassword): bool
    {
        $user = User::query()->find($userId);
        $hash = $user?->getAuthPassword();

        if ($user === null || $hash === null || $hash === '') {
            return false;
        }

        return Hash::check($plainPassword, $hash);
    }
}
