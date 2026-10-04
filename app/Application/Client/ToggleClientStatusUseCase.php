<?php

namespace App\Application\Client;

use App\Domain\Client\Client;
use App\Domain\Client\ClientRepositoryInterface;

final readonly class ToggleClientStatusUseCase
{
    public function __construct(
        private ClientRepositoryInterface $repository
    ) {}

    public function execute(int $id): Client
    {
        return $this->repository->toggleStatus($id);
    }
}
