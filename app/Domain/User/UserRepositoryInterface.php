<?php

namespace App\Domain\User;

interface UserRepositoryInterface
{
    public function find(int $id): ?User;
    public function findByLogin(string $login): ?User;
    public function all(): array;
    public function identityExists(string $field, string $value, ?int $except = null): bool;
    public function save(User $user): User;
    public function toggleStatus(int $id, int $actorId): User;
}
