<?php

namespace Tests\Feature\Category;

use App\Infrastructure\Persistence\Eloquent\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
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

    public function test_owner_lists_creates_updates_toggles_and_deletes_categories(): void
    {
        $headers = $this->ownerHeaders();

        // 1. List initial categories from seeder
        $response = $this->getJson('/api/categories', $headers)->assertOk();
        $this->assertCount(4, $response->json('data'));

        // 2. Create new category
        $createResponse = $this->postJson('/api/categories', [
            'name' => 'Monitores Gaming',
            'description' => 'Monitores de alta tasa de refresco',
        ], $headers)->assertCreated()
            ->assertJsonPath('data.name', 'Monitores Gaming')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.productsCount', 0);

        $id = $createResponse->json('data.id');

        // 3. Update category
        $this->putJson("/api/categories/$id", [
            'name' => 'Monitores y Pantallas',
            'description' => 'Monitores para oficina y gaming',
        ], $headers)->assertOk()
            ->assertJsonPath('data.name', 'Monitores y Pantallas');

        // 4. Toggle status to inactive
        $this->patchJson("/api/categories/$id/toggle-status", [], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');

        // Toggle back to active
        $this->patchJson("/api/categories/$id/toggle-status", [], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        // 5. Delete category without products
        $this->deleteJson("/api/categories/$id", [], $headers)
            ->assertOk()
            ->assertJsonPath('message', 'Categoría eliminada correctamente.');

        $this->assertDatabaseMissing('categories', ['id' => $id]);
    }

    public function test_category_name_must_be_unique_and_validated(): void
    {
        $headers = $this->ownerHeaders();

        // Required and max length
        $this->postJson('/api/categories', ['name' => ''], $headers)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        // Duplicate name (case insensitive)
        $this->postJson('/api/categories', ['name' => 'LAPTOPS'], $headers)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_cannot_delete_category_with_assigned_products(): void
    {
        $headers = $this->ownerHeaders();
        $category = CategoryModel::where('name', 'Laptops')->first();

        // Assign a product to this category
        ProductModel::create([
            'category_id' => $category->id,
            'name' => 'Laptop Gamer Asus',
            'sku' => 'LAP-0001',
            'cost_price' => 5000,
            'sale_price' => 6500,
            'stock' => 2,
            'min_stock' => 1,
            'status' => 'active',
        ]);

        $this->deleteJson("/api/categories/{$category->id}", [], $headers)
            ->assertUnprocessable();

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_seller_cannot_manage_categories(): void
    {
        $sellerHeaders = $this->sellerHeaders();

        $this->postJson('/api/categories', ['name' => 'Nueva Cat'], $sellerHeaders)
            ->assertForbidden();

        $this->deleteJson('/api/categories/1', [], $sellerHeaders)
            ->assertForbidden();
    }
}
