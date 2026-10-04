<?php

namespace App\Application\Client;

use App\Domain\Client\ClientRepositoryInterface;

final readonly class PaginateClientsUseCase
{
    public function __construct(
        private ClientRepositoryInterface $repository
    ) {}

    public function execute(array $filters = [], int $perPage = 15, int $page = 1): array
    {
        return $this->repository->paginate($filters, $perPage, $page);
    }
}
