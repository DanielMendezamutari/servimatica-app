<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Audit\LoginLog;
use App\Domain\Audit\LoginLogRepositoryInterface;

class EloquentLoginLogRepository implements LoginLogRepositoryInterface
{
    public function record(array $data): LoginLog
    {
        $m = LoginLogModel::create([
            'user_id' => $data['user_id'] ?? null,
            'attempted_username' => $data['attempted_username'],
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
            'status' => $data['status'],
            'created_at' => now(),
        ]);

        $m->load('user');
        return $this->toDomain($m);
    }

    public function paginate(int $page = 1, int $perPage = 15, array $filters = []): array
    {
        $query = LoginLogModel::with('user')->orderByDesc('id');

        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('attempted_username', 'like', "%{$s}%")
                    ->orWhere('ip_address', 'like', "%{$s}%");
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => collect($paginator->items())->map(fn($m) => $this->toDomain($m)->toArray())->toArray(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    private function toDomain(LoginLogModel $m): LoginLog
    {
        $labels = [
            'success' => 'Exitoso',
            'failed_credentials' => 'Credenciales inválidas',
            'failed_inactive_user' => 'Usuario inactivo',
        ];

        return new LoginLog(
            id: (int)$m->id,
            userId: $m->user_id ? (int)$m->user_id : null,
            attemptedUsername: $m->attempted_username,
            ipAddress: $m->ip_address,
            userAgent: $m->user_agent,
            status: $m->status,
            statusLabel: $labels[$m->status] ?? $m->status,
            user: $m->user ? [
                'id' => (int)$m->user->id,
                'name' => $m->user->name,
                'username' => $m->user->username ?? $m->user->email,
                'role' => $m->user->role,
            ] : null,
            createdAt: $m->created_at?->toDateTimeString()
        );
    }
}
