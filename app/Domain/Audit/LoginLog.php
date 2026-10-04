<?php

namespace App\Domain\Audit;

final readonly class LoginLog
{
    public function __construct(
        public int $id,
        public ?int $userId,
        public string $attemptedUsername,
        public ?string $ipAddress,
        public ?string $userAgent,
        public string $status,
        public ?string $statusLabel = null,
        public ?array $user = null,
        public ?string $createdAt = null
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'attempted_username' => $this->attemptedUsername,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'status' => $this->status,
            'status_label' => $this->statusLabel,
            'user' => $this->user,
            'created_at' => $this->createdAt,
        ];
    }
}
