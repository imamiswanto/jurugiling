<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    private function createCategory(string $name = 'Coffee'): Category
    {
        return Category::create([
            'name' => $name,
            'slug' => strtolower(str_replace(' ', '-', $name)),
            'description' => null,
        ]);
    }

    private function createProduct(
        Category $category,
        string $name = 'Arabica',
        bool $isActive = true
    ): Product {
        static $skuNumber = 1;

        return Product::create([
            'category_id' => $category->id,
            'sku' => 'TEST-' . $skuNumber++,
            'name' => $name,
            'slug' => strtolower(str_replace(' ', '-', $name)),
            'description' => 'Test product',
            'purchase_price' => 50000,
            'selling_price' => 75000,
            'stock' => 10,
            'minimum_stock' => 2,
            'is_active' => $isActive,
        ]);
    }

    private function authenticatedUser(): User
    {
        $user = User::factory()->create();

        $this->withToken(
            $user->createToken('product-api-test')->plainTextToken
        );

        return $user;
    }

    public function test_products_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/v1/products')
            ->assertUnauthorized();
    }

    public function test_product_list_only_contains_active_products(): void
    {
        $this->authenticatedUser();

        $category = $this->createCategory();
        $this->createProduct($category, 'Active Coffee', true);
        $this->createProduct($category, 'Inactive Coffee', false);

        $this->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Active Coffee'])
            ->assertJsonMissing(['name' => 'Inactive Coffee']);
    }

    public function test_product_response_does_not_expose_purchase_price(): void
    {
        $this->authenticatedUser();

        $category = $this->createCategory();
        $product = $this->createProduct($category);

        $this->getJson('/api/v1/products/' . $product->id)
            ->assertOk()
            ->assertJsonMissingPath('data.purchase_price')
            ->assertJsonPath('data.selling_price', 75000);
    }

    public function test_inactive_product_detail_returns_not_found(): void
    {
        $this->authenticatedUser();

        $category = $this->createCategory();
        $product = $this->createProduct($category, 'Inactive Coffee', false);

        $this->getJson('/api/v1/products/' . $product->id)
            ->assertNotFound();
    }

    public function test_product_search_filters_by_name(): void
    {
        $this->authenticatedUser();

        $category = $this->createCategory();
        $this->createProduct($category, 'Arabica Gayo');
        $this->createProduct($category, 'Robusta Temanggung');

        $this->getJson('/api/v1/products?search=Arabica')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Arabica Gayo'])
            ->assertJsonMissing(['name' => 'Robusta Temanggung']);
    }

    public function test_product_category_filter_works(): void
    {
        $this->authenticatedUser();

        $coffee = $this->createCategory('Coffee');
        $tea = $this->createCategory('Tea');

        $this->createProduct($coffee, 'Arabica');
        $this->createProduct($tea, 'Green Tea');

        $this->getJson('/api/v1/products?category_id=' . $tea->id)
            ->assertOk()
            ->assertJsonFragment(['name' => 'Green Tea'])
            ->assertJsonMissing(['name' => 'Arabica']);
    }

    public function test_product_pagination_respects_per_page(): void
    {
        $this->authenticatedUser();

        $category = $this->createCategory();

        for ($i = 1; $i <= 3; $i++) {
            $this->createProduct($category, 'Coffee ' . $i);
        }

        $this->getJson('/api/v1/products?per_page=2')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonCount(2, 'data');
    }

    public function test_product_pagination_rejects_per_page_above_limit(): void
    {
        $this->authenticatedUser();

        $this->getJson('/api/v1/products?per_page=200')
            ->assertUnprocessable();
    }
}
