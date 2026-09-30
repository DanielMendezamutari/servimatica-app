<?php

namespace App\Application\User;

use App\Domain\User\{User, UserRepositoryInterface};

final readonly class ToggleUserStatusUseCase
{
    public function __construct(private UserRepositoryInterface $users)
    {
    }
    public function execute(int $id, int $actorId): User
    {
        return $this->users->toggleStatus($id, $actorId);
    }
}
