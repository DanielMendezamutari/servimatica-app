<?php

namespace App\Application\Client;

use App\Domain\Client\Client;
use App\Domain\Client\ClientRepositoryInterface;

final readonly class CreateClientUseCase
{
    public function __construct(
        private ClientRepositoryInterface $repository
    ) {}

    public function execute(array $data): Client
    {
        return $this->repository->save($data);
    }
}
