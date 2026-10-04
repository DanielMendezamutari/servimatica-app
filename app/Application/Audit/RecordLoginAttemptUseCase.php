<?php

namespace App\Application\Audit;

use App\Domain\Audit\LoginLog;
use App\Domain\Audit\LoginLogRepositoryInterface;

final readonly class RecordLoginAttemptUseCase
{
    public function __construct(private LoginLogRepositoryInterface $repository) {}

    public function execute(
        string $attemptedUsername,
        string $status,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?int $userId = null
    ): LoginLog {
        return $this->repository->record([
            'user_id' => $userId,
            'attempted_username' => $attemptedUsername,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'status' => $status,
        ]);
    }
}
