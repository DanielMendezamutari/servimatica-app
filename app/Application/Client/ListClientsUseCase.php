<?php

namespace App\Application\Client;

use App\Domain\Client\ClientRepositoryInterface;

final readonly class ListClientsUseCase
{
    public function __construct(
        private ClientRepositoryInterface $repository
    ) {}

    public function execute(?string $query = null, int $perPage = 20): array
    {
        return $this->repository->search($query, $perPage);
    }
}
