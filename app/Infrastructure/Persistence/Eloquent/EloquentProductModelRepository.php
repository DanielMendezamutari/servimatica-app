<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\ProductModel\ProductModel;
use App\Domain\ProductModel\ProductModelRepositoryInterface;

class EloquentProductModelRepository implements ProductModelRepositoryInterface
{
    public function getByBrand(int $brandId, ?bool $activeOnly = null): array
    {
        $query = DeviceModel::with('brand')->where('brand_id', $brandId);

        if ($activeOnly !== null) {
            $query->where('is_active', $activeOnly);
        }

        return $query->orderBy('name')
            ->get()
            ->map(fn($m) => $this->toDomain($m))
            ->toArray();
    }

    public function findById(int $id): ?ProductModel
    {
        $m = DeviceModel::with('brand')->find($id);
        return $m ? $this->toDomain($m) : null;
    }

    public function create(array $data): ProductModel
    {
        $m = DeviceModel::create([
            'brand_id' => (int)$data['brand_id'],
            'name' => trim($data['name']),
            'notes' => $data['notes'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $m->load('brand');
        return $this->toDomain($m);
    }

    public function update(int $id, array $data): ProductModel
    {
        $m = DeviceModel::with('brand')->findOrFail($id);
        $m->update([
            'name' => trim($data['name'] ?? $m->name),
            'notes' => array_key_exists('notes', $data) ? $data['notes'] : $m->notes,
            'is_active' => isset($data['is_active']) ? (bool)$data['is_active'] : $m->is_active,
        ]);

        return $this->toDomain($m);
    }

    public function toggle(int $id): ProductModel
    {
        $m = DeviceModel::with('brand')->findOrFail($id);
        $m->is_active = !$m->is_active;
        $m->save();

        return $this->toDomain($m);
    }

    public function findOrCreateByName(int $brandId, string $name): ProductModel
    {
        $cleanName = trim($name);
        $m = DeviceModel::with('brand')->where('brand_id', $brandId)
            ->where('name', $cleanName)
            ->first();

        if (!$m) {
            $m = DeviceModel::create([
                'brand_id' => $brandId,
                'name' => $cleanName,
                'is_active' => true,
            ]);
            $m->load('brand');
        }

        return $this->toDomain($m);
    }

    private function toDomain(DeviceModel $m): ProductModel
    {
        return new ProductModel(
            id: (int)$m->id,
            brandId: (int)$m->brand_id,
            name: $m->name,
            notes: $m->notes,
            isActive: (bool)$m->is_active,
            brandName: $m->brand?->name,
            createdAt: $m->created_at?->toIso8601String()
        );
    }
}
