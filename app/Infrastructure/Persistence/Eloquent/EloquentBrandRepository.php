<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Brand\Brand;
use App\Domain\Brand\BrandRepositoryInterface;

class EloquentBrandRepository implements BrandRepositoryInterface
{
    public function all(string $search = '', ?bool $activeOnly = null): array
    {
        $query = BrandModel::withCount('models');

        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($activeOnly !== null) {
            $query->where('is_active', $activeOnly);
        }

        return $query->orderBy('name')
            ->get()
            ->map(fn($m) => $this->toDomain($m))
            ->toArray();
    }

    public function findById(int $id): ?Brand
    {
        $m = BrandModel::withCount('models')->find($id);
        return $m ? $this->toDomain($m) : null;
    }

    public function create(array $data): Brand
    {
        $m = BrandModel::create([
            'name' => trim($data['name']),
            'is_active' => $data['is_active'] ?? true,
        ]);

        return $this->toDomain($m);
    }

    public function update(int $id, array $data): Brand
    {
        $m = BrandModel::withCount('models')->findOrFail($id);
        $m->update([
            'name' => trim($data['name'] ?? $m->name),
            'is_active' => isset($data['is_active']) ? (bool)$data['is_active'] : $m->is_active,
        ]);

        return $this->toDomain($m);
    }

    public function toggle(int $id): Brand
    {
        $m = BrandModel::withCount('models')->findOrFail($id);
        $m->is_active = !$m->is_active;
        $m->save();

        return $this->toDomain($m);
    }

    public function options(): array
    {
        return BrandModel::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();
    }

    private function toDomain(BrandModel $m): Brand
    {
        return new Brand(
            id: (int)$m->id,
            name: $m->name,
            isActive: (bool)$m->is_active,
            modelsCount: (int)($m->models_count ?? 0),
            createdAt: $m->created_at?->toIso8601String()
        );
    }
}
