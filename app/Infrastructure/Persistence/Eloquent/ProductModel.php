<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductModel extends Model
{
    protected $table = 'products';

    protected $fillable = [
        'name',
        'description',
        'sku',
        'category_id',
        'subfamily_id',
        'brand_id',
        'product_model_id',
        'condition',
        'cost_price',
        'sale_price',
        'stock',
        'defective_stock',
        'min_stock',
        'warranty_days',
        'status',
        'image_path',
        'gallery_images',
    ];

    protected $attributes = [
        'status' => 'active',
        'condition' => 'nuevo',
        'stock' => 0,
        'defective_stock' => 0,
        'min_stock' => 0,
        'warranty_days' => 0,
    ];

    protected $hidden = [
        'cost_price',
        'min_stock',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'stock' => 'integer',
            'defective_stock' => 'integer',
            'warranty_days' => 'integer',
            'min_stock' => 'integer',
            'gallery_images' => 'array',
        ];
    }

    public function getImageUrlAttribute(): string
    {
        if (!empty($this->image_path)) {
            return asset('storage/' . $this->image_path);
        }

        if (!empty($this->gallery_images) && is_array($this->gallery_images) && count($this->gallery_images) > 0) {
            return asset('storage/' . $this->gallery_images[0]);
        }

        $categoryName = mb_strtolower($this->category?->name ?? '');
        if (str_contains($categoryName, 'laptop') || str_contains($categoryName, 'portátil')) {
            return asset('images/placeholders/laptop.svg');
        }
        if (str_contains($categoryName, 'pc') || str_contains($categoryName, 'computador') || str_contains($categoryName, 'escritorio')) {
            return asset('images/placeholders/pc.svg');
        }
        if (str_contains($categoryName, 'monitor') || str_contains($categoryName, 'pantalla')) {
            return asset('images/placeholders/monitor.svg');
        }
        if (str_contains($categoryName, 'accesorio') || str_contains($categoryName, 'periférico')) {
            return asset('images/placeholders/accessory.svg');
        }

        return asset('images/placeholders/default.svg');
    }

    public function getGalleryUrlsAttribute(): array
    {
        $gallery = is_array($this->gallery_images) ? $this->gallery_images : [];
        return array_values(array_map(fn($path) => asset('storage/' . $path), $gallery));
    }


    public function category(): BelongsTo
    {
        return $this->belongsTo(CategoryModel::class, 'category_id');
    }

    public function subfamily(): BelongsTo
    {
        return $this->belongsTo(CategoryModel::class, 'subfamily_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(BrandModel::class, 'brand_id');
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(DeviceModel::class, 'product_model_id');
    }
}
