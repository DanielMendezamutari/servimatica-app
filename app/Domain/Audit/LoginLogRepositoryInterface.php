<?php

namespace App\Domain\Audit;

interface LoginLogRepositoryInterface
{
    public function record(array $data): LoginLog;
    public function paginate(int $page = 1, int $perPage = 15, array $filters = []): array;
}
