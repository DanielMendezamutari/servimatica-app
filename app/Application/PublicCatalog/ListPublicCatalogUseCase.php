<?php

namespace App\Application\PublicCatalog;

use App\Infrastructure\Persistence\Eloquent\CompanySettingModel;
use App\Infrastructure\Persistence\Eloquent\ProductModel;

final class ListPublicCatalogUseCase
{
    public function execute(array $filters = []): array
    {
        $company = CompanySettingModel::first();
        $companyPhone = $company?->mobile ?: ($company?->phone ?: '59178901234');
        $cleanPhone = preg_replace('/\D+/', '', $companyPhone);
        if (strlen($cleanPhone) === 8) {
            $cleanPhone = '591' . $cleanPhone;
        }

        $query = ProductModel::with(['category', 'brand', 'model'])
            ->where('status', 'active')
            ->where('stock', '>', 0)
            ->whereHas('category', fn($q) => $q->where('status', 'active'));

        if (!empty($filters['search'])) {
            $term = '%' . trim($filters['search']) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('sku', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['brand_id'])) {
            $query->where('brand_id', $filters['brand_id']);
        }

        $products = $query->orderBy('name')->get();

        return $products->map(function (ProductModel $m) use ($cleanPhone) {
            $galleryUrls = $m->gallery_urls;
            $has360 = count($galleryUrls) >= 2;

            $message = sprintf(
                "¡Hola! Vi en su Vitrina / TikTok Live la *%s* (Bs. %s). ¿Aún está disponible para entrega inmediata? 💻✨",
                $m->name,
                number_format((float) $m->sale_price, 2, '.', ',')
            );
            $whatsappUrl = "https://wa.me/{$cleanPhone}?text=" . rawurlencode($message);

            return (new PublicProductDTO(
                id: (int) $m->id,
                name: $m->name,
                description: $m->description,
                sku: $m->sku,
                categoryName: $m->category?->name ?? 'General',
                brandName: $m->brand?->name,
                modelName: $m->model?->name,
                condition: $m->condition ?? 'nuevo',
                salePrice: (float) $m->sale_price,
                stock: (int) $m->stock,
                warrantyDays: (int) ($m->warranty_days ?? 0),
                imageUrl: $m->image_url,
                galleryUrls: $galleryUrls,
                has360: $has360,
                whatsappOrderLink: $whatsappUrl
            ))->toArray();
        })->all();
    }
}
