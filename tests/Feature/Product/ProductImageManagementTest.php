<?php

namespace Tests\Feature\Product;

use App\Infrastructure\Persistence\Eloquent\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageManagementTest extends TestCase
{
    use RefreshDatabase;

    private function ownerHeaders(): array
    {
        $this->seed();
        $token = $this->postJson('/api/auth/login', ['login' => 'admin', 'pin' => '1234'])->json('accessToken');
        return ['Authorization' => "Bearer $token"];
    }

    private function sellerHeaders(): array
    {
        $headers = $this->ownerHeaders();
        $this->postJson('/api/users', [
            'name' => 'Vendedor Test',
            'username' => 'vendedor',
            'email' => 'vendedor@servimatica.com',
            'password' => 'password123',
            'pin' => '9999',
            'role' => 'vendedor',
        ], $headers);

        $token = $this->postJson('/api/auth/login', ['login' => 'vendedor', 'pin' => '9999'])->json('accessToken');
        return ['Authorization' => "Bearer $token"];
    }

    public function test_owner_can_create_product_with_cover_and_gallery_images(): void
    {
        Storage::fake('public');
        $headers = $this->ownerHeaders();
        $category = CategoryModel::first();

        $coverFile = UploadedFile::fake()->image('cover.jpg', 800, 800);
        $galleryFile1 = UploadedFile::fake()->image('angle1.jpg', 800, 800);
        $galleryFile2 = UploadedFile::fake()->image('angle2.jpg', 800, 800);

        $payload = [
            'name' => 'Laptop Gamer Asus TUF',
            'categoryId' => $category->id,
            'costPrice' => 5000,
            'salePrice' => 6200,
            'stock' => 5,
            'minStock' => 1,
            'condition' => 'nuevo',
            'image' => $coverFile,
            'gallery' => [$galleryFile1, $galleryFile2],
        ];

        $res = $this->post('/api/products', $payload, $headers);
        $res->assertCreated();

        $data = $res->json('data');
        $this->assertNotEmpty($data['image_path']);
        $this->assertCount(2, $data['gallery_images']);
        $this->assertStringContainsString('storage/products/', $data['image_url']);
        $this->assertCount(2, $data['gallery_urls']);

        Storage::disk('public')->assertExists($data['image_path']);
        foreach ($data['gallery_images'] as $galleryPath) {
            Storage::disk('public')->assertExists($galleryPath);
        }
    }

    public function test_invalid_image_mime_or_size_is_rejected(): void
    {
        Storage::fake('public');
        $headers = $this->ownerHeaders();
        $category = CategoryModel::first();

        $invalidFile = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $res = $this->postJson('/api/products', [
            'name' => 'Laptop Invalida',
            'categoryId' => $category->id,
            'costPrice' => 1000,
            'salePrice' => 1200,
            'stock' => 1,
            'image' => $invalidFile,
        ], $headers);

        $res->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_owner_can_update_product_images(): void
    {
        Storage::fake('public');
        $headers = $this->ownerHeaders();
        $category = CategoryModel::first();

        $product = ProductModel::create([
            'name' => 'PC Escritorio Ryzen',
            'sku' => 'PC-001',
            'category_id' => $category->id,
            'cost_price' => 2000,
            'sale_price' => 2500,
            'stock' => 2,
            'status' => 'active',
        ]);

        $newCover = UploadedFile::fake()->image('new_pc.jpg', 600, 600);
        $newGallery = [UploadedFile::fake()->image('pc_angle1.jpg', 600, 600)];

        $res = $this->post("/api/products/{$product->id}", [
            '_method' => 'PUT',
            'name' => 'PC Escritorio Ryzen Pro',
            'categoryId' => $category->id,
            'costPrice' => 2000,
            'salePrice' => 2600,
            'image' => $newCover,
            'gallery' => $newGallery,
        ], $headers);

        $res->assertOk();
        $data = $res->json('data');
        $this->assertNotEmpty($data['image_path']);
        $this->assertCount(1, $data['gallery_images']);
        Storage::disk('public')->assertExists($data['image_path']);
    }

    public function test_seller_cannot_upload_or_modify_products(): void
    {
        $headers = $this->sellerHeaders();
        $category = CategoryModel::first();

        $res = $this->postJson('/api/products', [
            'name' => 'Vendedor Laptop',
            'categoryId' => $category->id,
            'salePrice' => 3000,
        ], $headers);

        $res->assertForbidden();
    }
}
