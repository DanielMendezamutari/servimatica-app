<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Supplier\Supplier;
use App\Domain\Supplier\SupplierRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class EloquentSupplierRepository implements SupplierRepositoryInterface
{
    public function findById(int $id): ?Supplier
    {
        $model = SupplierModel::find($id);
        return $model ? $this->map($model) : null;
    }

    public function save(array $data): Supplier
    {
        $this->validateUnique($data);

        $model = SupplierModel::create([
            'name' => trim($data['name']),
            'nit' => !empty($data['nit']) ? trim($data['nit']) : null,
            'contact_name' => !empty($data['contact_name']) ? trim($data['contact_name']) : null,
            'phone' => !empty($data['phone']) ? trim($data['phone']) : null,
            'email' => !empty($data['email']) ? trim($data['email']) : null,
            'city' => !empty($data['city']) ? trim($data['city']) : null,
            'address' => !empty($data['address']) ? trim($data['address']) : null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return $this->map($model);
    }

    public function update(int $id, array $data): Supplier
    {
        $model = SupplierModel::findOrFail($id);
        $this->validateUnique($data, $id);

        $model->update([
            'name' => trim($data['name']),
            'nit' => !empty($data['nit']) ? trim($data['nit']) : null,
            'contact_name' => !empty($data['contact_name']) ? trim($data['contact_name']) : null,
            'phone' => !empty($data['phone']) ? trim($data['phone']) : null,
            'email' => !empty($data['email']) ? trim($data['email']) : null,
            'city' => !empty($data['city']) ? trim($data['city']) : null,
            'address' => !empty($data['address']) ? trim($data['address']) : null,
            'is_active' => $data['is_active'] ?? $model->is_active,
        ]);

        return $this->map($model);
    }

    public function toggleStatus(int $id): Supplier
    {
        return DB::transaction(function () use ($id) {
            $model = SupplierModel::lockForUpdate()->findOrFail($id);
            $model->update(['is_active' => !$model->is_active]);
            return $this->map($model);
        });
    }

    public function paginate(int $page = 1, int $perPage = 15, ?string $search = null, ?string $status = null): array
    {
        $query = SupplierModel::query();

        if (!empty($search)) {
            $term = trim($search);
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('nit', 'like', "%{$term}%")
                    ->orWhere('contact_name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('city', 'like', "%{$term}%");
            });
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $paginator = $query->orderBy('name', 'asc')->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => collect($paginator->items())->map(fn($m) => $this->map($m)->toArray())->all(),
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ];
    }

    public function options(): array
    {
        return SupplierModel::where('is_active', true)
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'nit', 'phone', 'contact_name'])
            ->toArray();
    }

    private function validateUnique(array $data, ?int $id = null): void
    {
        $name = trim($data['name'] ?? '');
        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'El nombre del proveedor es requerido.']);
        }

        $query = SupplierModel::whereRaw('LOWER(name) = ?', [mb_strtolower($name)]);
        if ($id) {
            $query->where('id', '!=', $id);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages(['name' => 'Ya existe un proveedor registrado con ese nombre.']);
        }
    }

    private function map(SupplierModel $m): Supplier
    {
        return new Supplier(
            id: (int)$m->id,
            name: (string)$m->name,
            nit: $m->nit ? (string)$m->nit : null,
            contactName: $m->contact_name ? (string)$m->contact_name : null,
            phone: $m->phone ? (string)$m->phone : null,
            email: $m->email ? (string)$m->email : null,
            city: $m->city ? (string)$m->city : null,
            address: $m->address ? (string)$m->address : null,
            isActive: (bool)$m->is_active,
            createdAt: $m->created_at?->format('Y-m-d H:i:s')
        );
    }
}
