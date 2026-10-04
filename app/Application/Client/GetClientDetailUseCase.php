<?php

namespace App\Application\Client;

use App\Domain\Client\ClientRepositoryInterface;
use RuntimeException;

final readonly class GetClientDetailUseCase
{
    public function __construct(
        private ClientRepositoryInterface $repository
    ) {}

    public function execute(int $id): array
    {
        $client = $this->repository->findById($id);
        if (! $client) {
            throw new RuntimeException("Cliente con ID {$id} no encontrado.", 404);
        }

        $stats = $this->repository->getClientStats($id);

        $data = $client->toArray();
        $data['stats'] = $stats;

        return $data;
    }
}
