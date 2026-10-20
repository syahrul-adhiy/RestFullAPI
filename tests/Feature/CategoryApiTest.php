<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->staff = User::factory()->create(['role' => 'staff']);
    }

    public function test_public_user_can_list_categories(): void
    {
        Category::create(['name' => 'Snapback', 'slug' => 'snapback']);
        Category::create(['name' => 'Beanie', 'slug' => 'beanie']);

        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');
    }

    public function test_admin_can_create_category(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/categories', [
            'name' => 'Bucket Hat',
            'description' => 'Topi santai melingkar.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Bucket Hat');

        $this->assertDatabaseHas('categories', ['name' => 'Bucket Hat']);
    }

    public function test_staff_cannot_create_category(): void
    {
        $response = $this->actingAs($this->staff, 'sanctum')->postJson('/api/v1/categories', [
            'name' => 'Restricted Category',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_create_category_validation_fails_without_name(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/categories', [
            'description' => 'Missing name field',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_cannot_delete_category_with_active_products(): void
    {
        $category = Category::create(['name' => 'Snapback', 'slug' => 'snapback']);

        Product::create([
            'category_id' => $category->id,
            'user_id' => $this->admin->id,
            'name' => 'Test Hat',
            'sku' => 'TEST-001',
            'slug' => 'test-hat',
            'price' => 100000,
            'stock' => 10,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')->deleteJson("/api/v1/categories/{$category->id}");

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }
}
