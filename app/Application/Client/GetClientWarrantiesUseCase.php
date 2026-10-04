<?php

namespace App\Application\Client;

use App\Domain\Client\ClientRepositoryInterface;

final readonly class GetClientWarrantiesUseCase
{
    public function __construct(
        private ClientRepositoryInterface $repository
    ) {}

    public function execute(int $id): array
    {
        return $this->repository->getClientWarranties($id);
    }
}
