<?php

namespace Tests\Feature\Catalog;

use App\Infrastructure\Persistence\Eloquent\BrandModel;
use App\Infrastructure\Persistence\Eloquent\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCatalogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_can_list_catalog_without_authentication(): void
    {
        $this->seed();

        $cat = CategoryModel::first();
        $brand = BrandModel::create(['name' => 'Dell', 'slug' => 'dell']);

        // 1. Producto activo y con stock
        $activeProd = ProductModel::create([
            'name' => 'Dell Latitude 5420',
            'sku' => 'LAP-DEL-001',
            'category_id' => $cat->id,
            'brand_id' => $brand->id,
            'cost_price' => 2000.00,
            'sale_price' => 3100.00,
            'stock' => 4,
            'warranty_days' => 180,
            'status' => 'active',
        ]);

        // 2. Producto inactivo (no debe figurar)
        ProductModel::create([
            'name' => 'Laptop Inactiva',
            'sku' => 'LAP-OFF-001',
            'category_id' => $cat->id,
            'cost_price' => 1000.00,
            'sale_price' => 1500.00,
            'stock' => 2,
            'status' => 'inactive',
        ]);

        // 3. Producto sin stock (no debe figurar en vitrina)
        ProductModel::create([
            'name' => 'Laptop Agotada',
            'sku' => 'LAP-OUT-001',
            'category_id' => $cat->id,
            'cost_price' => 1000.00,
            'sale_price' => 1500.00,
            'stock' => 0,
            'status' => 'active',
        ]);

        $res = $this->getJson('/api/public/catalog');
        $res->assertOk();

        $data = $res->json('data');
        $this->assertNotEmpty($data);

        // Solo debe figurar el producto activo y con stock
        $names = array_column($data, 'name');
        $this->assertContains('Dell Latitude 5420', $names);
        $this->assertNotContains('Laptop Inactiva', $names);
        $this->assertNotContains('Laptop Agotada', $names);

        // SEGURIDAD: Nunca exponer costos ni proveedores al público
        $firstItem = $data[0];
        $this->assertArrayNotHasKey('cost_price', $firstItem);
        $this->assertArrayNotHasKey('costPrice', $firstItem);
        $this->assertArrayNotHasKey('supplier_id', $firstItem);
        $this->assertArrayNotHasKey('supplier', $firstItem);
        $this->assertArrayNotHasKey('min_stock', $firstItem);

        // Campos esenciales para la vitrina TikTok Live
        $this->assertArrayHasKey('id', $firstItem);
        $this->assertArrayHasKey('name', $firstItem);
        $this->assertArrayHasKey('sale_price', $firstItem);
        $this->assertArrayHasKey('image_url', $firstItem);
        $this->assertArrayHasKey('gallery_urls', $firstItem);
        $this->assertArrayHasKey('has_360', $firstItem);
        $this->assertArrayHasKey('whatsapp_order_link', $firstItem);
    }

    public function test_public_catalog_can_filter_by_category_and_search(): void
    {
        $this->seed();
        $cat1 = CategoryModel::first();
        $cat2 = CategoryModel::create(['name' => 'Monitores', 'slug' => 'monitores']);

        ProductModel::create([
            'name' => 'Laptop Gamer Legion',
            'sku' => 'LAP-LEG-001',
            'category_id' => $cat1->id,
            'cost_price' => 4000,
            'sale_price' => 5500,
            'stock' => 2,
            'status' => 'active',
        ]);

        ProductModel::create([
            'name' => 'Monitor LG 27 UltraGear',
            'sku' => 'MON-LG-001',
            'category_id' => $cat2->id,
            'cost_price' => 1200,
            'sale_price' => 1700,
            'stock' => 5,
            'status' => 'active',
        ]);

        // Filtrar por categoría
        $resCat = $this->getJson("/api/public/catalog?category_id={$cat2->id}");
        $resCat->assertOk();
        $this->assertCount(1, $resCat->json('data'));
        $this->assertEquals('Monitor LG 27 UltraGear', $resCat->json('data.0.name'));

        // Filtrar por texto de búsqueda
        $resSearch = $this->getJson('/api/public/catalog?search=Legion');
        $resSearch->assertOk();
        $this->assertCount(1, $resSearch->json('data'));
        $this->assertEquals('Laptop Gamer Legion', $resSearch->json('data.0.name'));
    }
}
