<?php

namespace App\Application\User;

use App\Domain\User\UserRepositoryInterface;

final readonly class ListUsersUseCase
{
    public function __construct(private UserRepositoryInterface $users)
    {
    }
    public function execute(): array
    {
        return array_map(fn ($user) => $user->publicData(), $this->users->all());
    }
}
