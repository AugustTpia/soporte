<?php

namespace App\Identity\Application\UseCases;

use App\Identity\Domain\Entities\User;
use App\Identity\Domain\Repositories\UserRepository;
use App\Shared\Domain\Exceptions\DomainException;

final readonly class GetUser
{
    public function __construct(private UserRepository $users) {}

    public function execute(int $userId): User
    {
        return $this->users->find($userId)
            ?? throw DomainException::because('El usuario no existe.');
    }
}
