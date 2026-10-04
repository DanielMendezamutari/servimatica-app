<?php

namespace App\Application\Audit;

use App\Domain\Audit\LoginLogRepositoryInterface;

final readonly class ListLoginLogsUseCase
{
    public function __construct(private LoginLogRepositoryInterface $repository) {}

    public function execute(int $page = 1, int $perPage = 15, array $filters = []): array
    {
        return $this->repository->paginate($page, $perPage, $filters);
    }
}
