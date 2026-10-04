<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Product\{Product, Price, Sku, StockQuantity, ProductRepositoryInterface};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class EloquentProductRepository implements ProductRepositoryInterface
{
    private function buildQuery(array $filters, bool $owner)
    {
        $query = ProductModel::with(['category', 'subfamily', 'brand', 'model']);

        if (!$owner) {
            $query->where('status', 'active')
                ->whereHas('category', fn($q) => $q->where('status', 'active'));
        } elseif (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(fn($q) => $q->where('name', 'like', $term)->orWhere('sku', 'like', $term));
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['subfamily_id'])) {
            $query->where('subfamily_id', $filters['subfamily_id']);
        }

        if (!empty($filters['brand_id'])) {
            $query->where('brand_id', $filters['brand_id']);
        }

        if (!empty($filters['condition'])) {
            $query->where('condition', $filters['condition']);
        }

        return $query;
    }

    public function paginate(array $filters, bool $owner): array
    {
        $query = $this->buildQuery($filters, $owner);
        $page = $query->orderBy('name')->orderBy('id')->paginate($filters['per_page'] ?? 15, ['*'], 'page', $filters['page'] ?? 1);

        return [
            'data' => $page->getCollection()->map(fn($m) => $this->map($m)->toArray($owner))->all(),
            'meta' => [
                'currentPage' => $page->currentPage(),
                'lastPage' => $page->lastPage(),
                'perPage' => $page->perPage(),
                'total' => $page->total(),
            ],
        ];
    }

    public function getForExport(array $filters, bool $owner): array
    {
        $query = $this->buildQuery($filters, $owner);
        return $query->orderBy('name')->orderBy('id')->get()->map(fn($m) => $this->map($m)->toArray($owner))->all();
    }

    private function attributes(array $data): array
    {
        $mapped = [
            'name' => trim($data['name']),
            'description' => $data['description'] ?? null,
            'category_id' => $data['categoryId'] ?? $data['category_id'],
            'subfamily_id' => $data['subfamilyId'] ?? $data['subfamily_id'] ?? null,
            'brand_id' => $data['brandId'] ?? $data['brand_id'] ?? null,
            'product_model_id' => $data['productModelId'] ?? $data['product_model_id'] ?? null,
            'condition' => $data['condition'] ?? 'nuevo',
            'cost_price' => (new Price($data['costPrice'] ?? $data['cost_price'] ?? 0))->value,
            'sale_price' => (new Price($data['salePrice'] ?? $data['sale_price']))->value,
            'min_stock' => (new StockQuantity((int)($data['minStock'] ?? $data['min_stock'] ?? 0)))->value,
            'warranty_days' => max(0, (int)($data['warrantyDays'] ?? $data['warranty_days'] ?? 0)),
        ];

        if (isset($data['status'])) {
            $mapped['status'] = $data['status'];
        }

        if (array_key_exists('image_path', $data) || array_key_exists('imagePath', $data)) {
            $mapped['image_path'] = $data['image_path'] ?? $data['imagePath'] ?? null;
        }

        if (array_key_exists('gallery_images', $data) || array_key_exists('galleryImages', $data)) {
            $mapped['gallery_images'] = $data['gallery_images'] ?? $data['galleryImages'] ?? null;
        }

        return $mapped;
    }

    private function ensureSku(string $sku, ?int $id = null): string
    {
        $sku = (new Sku($sku))->value;
        if (ProductModel::whereRaw('LOWER(sku) = ?', [mb_strtolower($sku)])->when($id, fn($q) => $q->where('id', '!=', $id))->exists()) {
            throw ValidationException::withMessages(['sku' => 'El código SKU ya está en uso por otro producto.']);
        }
        return $sku;
    }

    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $categoryId = $data['categoryId'] ?? $data['category_id'];
            $categories = CategoryModel::orderBy('id')->lockForUpdate()->get();
            $category = $categories->firstWhere('id', $categoryId);
            abort_unless($category, 422, 'La categoría seleccionada no existe.');

            $sku = trim($data['sku'] ?? '');
            if ($sku === '') {
                $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', Str::ascii($category->name)), 0, 3));
                $prefix = str_pad($prefix, 3, 'X');
                $max = 0;
                foreach (ProductModel::where('category_id', $category->id)->pluck('sku') as $existing) {
                    if (preg_match('/^' . preg_quote($prefix, '/') . '-(\d+)$/', $existing, $match)) {
                        $max = max($max, (int)$match[1]);
                    }
                }
                do {
                    $sku = $prefix . '-' . str_pad((string)++$max, 4, '0', STR_PAD_LEFT);
                } while (ProductModel::where('sku', $sku)->exists());
            }

            $attrs = $this->attributes($data);
            $attrs['sku'] = $this->ensureSku($sku);
            $attrs['stock'] = (new StockQuantity((int)($data['stock'] ?? 0)))->value;

            $m = ProductModel::create($attrs);
            return $this->map($m->load(['category', 'subfamily', 'brand', 'model']));
        }, 3);
    }

    public function update(int $id, array $data): Product
    {
        return DB::transaction(function () use ($id, $data) {
            $categoryId = $data['categoryId'] ?? $data['category_id'];
            CategoryModel::whereKey($categoryId)->lockForUpdate()->firstOrFail();
            $m = ProductModel::lockForUpdate()->findOrFail($id);

            $attrs = $this->attributes($data);
            if (!empty($data['sku'])) {
                $attrs['sku'] = $this->ensureSku($data['sku'], $id);
            }

            $m->update($attrs);
            return $this->map($m->load(['category', 'subfamily', 'brand', 'model']));
        }, 3);
    }

    public function toggle(int $id): Product
    {
        return DB::transaction(function () use ($id) {
            $m = ProductModel::lockForUpdate()->findOrFail($id);
            $m->update(['status' => $m->status === 'active' ? 'inactive' : 'active']);
            return $this->map($m->load(['category', 'subfamily', 'brand', 'model']));
        });
    }

    private function map(ProductModel $m): Product
    {
        return new Product(
            id: (int)$m->id,
            name: $m->name,
            description: $m->description,
            sku: new Sku($m->sku),
            categoryId: (int)$m->category_id,
            categoryName: $m->category?->name ?? '',
            costPrice: new Price($m->cost_price ?? 0),
            salePrice: new Price($m->sale_price),
            stock: new StockQuantity($m->stock),
            minStock: new StockQuantity($m->min_stock),
            status: $m->status,
            subfamilyId: $m->subfamily_id ? (int)$m->subfamily_id : null,
            subfamilyName: $m->subfamily?->name,
            brandId: $m->brand_id ? (int)$m->brand_id : null,
            brandName: $m->brand?->name,
            productModelId: $m->product_model_id ? (int)$m->product_model_id : null,
            productModelName: $m->model?->name,
            condition: $m->condition ?? 'nuevo',
            createdAt: $m->created_at?->toISOString(),
            warrantyDays: (int)($m->warranty_days ?? 0),
            defectiveStock: (int)($m->defective_stock ?? 0),
            imagePath: $m->image_path,
            galleryImages: is_array($m->gallery_images) ? $m->gallery_images : null,
            imageUrl: $m->image_url,
            galleryUrls: $m->gallery_urls
        );
    }
}

