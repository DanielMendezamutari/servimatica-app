<?php

namespace App\Application\Client;

use App\Domain\Client\Client;
use App\Domain\Client\ClientRepositoryInterface;

final readonly class UpdateClientUseCase
{
    public function __construct(
        private ClientRepositoryInterface $repository
    ) {}

    public function execute(int $id, array $data): Client
    {
        return $this->repository->update($id, $data);
    }
}
