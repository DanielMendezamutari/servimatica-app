<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Category\{Category, CategoryName, CategoryRepositoryInterface};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class EloquentCategoryRepository implements CategoryRepositoryInterface
{
    public function all(string $search = '', bool $tree = false): array
    {
        $query = CategoryModel::with(['parent', 'children'])
            ->withCount(['products', 'subfamilyProducts']);

        if ($search !== '') {
            $query->where('name', 'like', '%' . $search . '%');
        }

        if ($tree) {
            // Only root categories with their children
            return $query->whereNull('parent_id')
                ->orderBy('name')
                ->get()
                ->map(fn($m) => $this->map($m))
                ->all();
        }

        return $query->orderBy('name')
            ->get()
            ->map(fn($m) => $this->map($m))
            ->all();
    }

    public function options(bool $owner): array
    {
        return CategoryModel::when(!$owner, fn($q) => $q->where('status', 'active'))
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();
    }

    public function subfamilies(int $parentId): array
    {
        return CategoryModel::where('parent_id', $parentId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id'])
            ->toArray();
    }

    private function name(array $data, ?int $id = null): string
    {
        $name = (new CategoryName($data['name']))->value;
        if (CategoryModel::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->when($id, fn($q) => $q->where('id', '!=', $id))->exists()) {
            throw ValidationException::withMessages(['name' => 'El nombre de categoría ya está en uso.']);
        }
        return $name;
    }

    public function create(array $data): Category
    {
        $data['name'] = $this->name($data);
        $parentId = !empty($data['parent_id']) ? (int)$data['parent_id'] : null;

        if ($parentId) {
            $parent = CategoryModel::find($parentId);
            if (!$parent) {
                throw ValidationException::withMessages(['parent_id' => 'La categoría principal especificada no existe.']);
            }
        }

        $created = CategoryModel::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'parent_id' => $parentId,
            'status' => $data['status'] ?? 'active',
        ]);

        return $this->map($created->load(['parent', 'children'])->loadCount(['products', 'subfamilyProducts']));
    }

    public function update(int $id, array $data): Category
    {
        $model = CategoryModel::findOrFail($id);
        $data['name'] = $this->name($data, $id);
        $parentId = !empty($data['parent_id']) ? (int)$data['parent_id'] : null;

        if ($parentId && $parentId === $id) {
            throw ValidationException::withMessages(['parent_id' => 'Una categoría no puede ser su propia categoría padre.']);
        }

        $model->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? $model->description,
            'parent_id' => $parentId,
            'status' => $data['status'] ?? $model->status,
        ]);

        return $this->map($model->load(['parent', 'children'])->loadCount(['products', 'subfamilyProducts']));
    }

    public function toggle(int $id): Category
    {
        return DB::transaction(function () use ($id) {
            $m = CategoryModel::lockForUpdate()->findOrFail($id);
            $m->update(['status' => $m->status === 'active' ? 'inactive' : 'active']);
            return $this->map($m->load(['parent', 'children'])->loadCount(['products', 'subfamilyProducts']));
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $m = CategoryModel::lockForUpdate()->findOrFail($id);

            $childrenCount = $m->children()->count();
            if ($childrenCount > 0) {
                throw ValidationException::withMessages(['category' => "No se puede eliminar la categoría porque contiene {$childrenCount} subfamilias vinculadas. Reasígnelas o elimínelas primero."]);
            }

            $productsCount = $m->products()->count() + $m->subfamilyProducts()->count();
            if ($productsCount > 0) {
                throw ValidationException::withMessages(['category' => "No se puede eliminar la categoría porque tiene {$productsCount} productos asignados. Reasígnelos primero."]);
            }

            $m->delete();
        });
    }

    private function map(CategoryModel $m): Category
    {
        $children = $m->relationLoaded('children')
            ? $m->children->map(fn($c) => [
                'id' => (int)$c->id,
                'name' => $c->name,
                'status' => $c->status,
                'parent_id' => (int)$c->parent_id,
            ])->toArray()
            : [];

        $totalProducts = (int)($m->products_count ?? 0) + (int)($m->subfamily_products_count ?? 0);

        return new Category(
            id: (int)$m->id,
            name: new CategoryName($m->name),
            description: $m->description,
            status: $m->status,
            productsCount: $totalProducts,
            parentId: $m->parent_id ? (int)$m->parent_id : null,
            parentName: $m->parent?->name,
            children: $children,
            createdAt: $m->created_at?->toISOString()
        );
    }
}
