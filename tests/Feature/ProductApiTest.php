<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $staff;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->staff = User::factory()->create(['role' => 'staff']);

        $this->category = Category::create([
            'name' => 'Snapback',
            'slug' => 'snapback',
        ]);
    }

    public function test_can_list_products_with_search_and_filters(): void
    {
        Product::create([
            'category_id' => $this->category->id,
            'user_id' => $this->admin->id,
            'name' => 'Vintage Snapback Red',
            'sku' => 'SNP-RED-001',
            'slug' => 'vintage-snapback-red',
            'price' => 150000,
            'stock' => 10,
            'status' => 'published',
        ]);

        Product::create([
            'category_id' => $this->category->id,
            'user_id' => $this->admin->id,
            'name' => 'Beanie Charcoal',
            'sku' => 'BNE-CHR-001',
            'slug' => 'beanie-charcoal',
            'price' => 80000,
            'stock' => 0,
            'status' => 'published',
        ]);

        // Search test
        $responseSearch = $this->getJson('/api/v1/products?search=Snapback');
        $responseSearch->assertStatus(200)
            ->assertJsonPath('meta.pagination.total', 1)
            ->assertJsonPath('data.0.name', 'Vintage Snapback Red');

        // In-stock filter test
        $responseInStock = $this->getJson('/api/v1/products?in_stock=true');
        $responseInStock->assertStatus(200)
            ->assertJsonPath('meta.pagination.total', 1)
            ->assertJsonPath('data.0.name', 'Vintage Snapback Red');

        // Price filter test
        $responsePrice = $this->getJson('/api/v1/products?max_price=100000');
        $responsePrice->assertStatus(200)
            ->assertJsonPath('meta.pagination.total', 1)
            ->assertJsonPath('data.0.name', 'Beanie Charcoal');
    }

    public function test_authenticated_user_can_create_product_with_file_upload(): void
    {
        $fakeImage = UploadedFile::fake()->create('snapback.jpg', 200, 'image/jpeg');

        $payload = [
            'category_id' => $this->category->id,
            'name' => 'Pro Street Snapback Black',
            'sku' => 'TOPI-PRO-001',
            'price' => 175000,
            'stock' => 25,
            'material' => 'Cotton Twill',
            'status' => 'published',
            'image' => $fakeImage,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/products', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Pro Street Snapback Black')
            ->assertJsonPath('data.stock', 25);

        $this->assertDatabaseHas('products', [
            'sku' => 'TOPI-PRO-001',
            'user_id' => $this->admin->id,
        ]);

        // Verify initial stock movement was automatically recorded
        $this->assertDatabaseHas('stock_movements', [
            'type' => 'in',
            'quantity' => 25,
            'current_stock' => 25,
        ]);
    }

    public function test_create_product_validation_fails_on_duplicate_sku(): void
    {
        Product::create([
            'category_id' => $this->category->id,
            'user_id' => $this->admin->id,
            'name' => 'First Product',
            'sku' => 'DUPLICATE-SKU',
            'slug' => 'first-product',
            'price' => 100000,
            'stock' => 5,
        ]);

        $payload = [
            'category_id' => $this->category->id,
            'name' => 'Second Product',
            'sku' => 'DUPLICATE-SKU',
            'price' => 120000,
            'stock' => 10,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/products', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['sku']);
    }

    public function test_dedicated_upload_image_endpoint(): void
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'user_id' => $this->admin->id,
            'name' => 'Product For Image Test',
            'sku' => 'IMG-TEST-001',
            'slug' => 'product-for-image-test',
            'price' => 100000,
            'stock' => 5,
        ]);

        $image = UploadedFile::fake()->create('hat_photo.png', 150, 'image/png');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/upload-image", [
                'image' => $image,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $freshProduct = $product->fresh();
        $this->assertNotNull($freshProduct->image_path);
        Storage::disk('public')->assertExists($freshProduct->image_path);
    }

    public function test_concurrency_safe_stock_adjustment(): void
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'user_id' => $this->admin->id,
            'name' => 'Stock Test Product',
            'sku' => 'STK-001',
            'slug' => 'stock-test-product',
            'price' => 100000,
            'stock' => 10,
        ]);

        // Add 15 items
        $responseIn = $this->actingAs($this->staff, 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/adjust-stock", [
                'type' => 'in',
                'quantity' => 15,
                'notes' => 'Penerimaan stok dari supplier.',
            ]);

        $responseIn->assertStatus(200)
            ->assertJsonPath('data.stock', 25);

        // Deduct 5 items
        $responseOut = $this->actingAs($this->staff, 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/adjust-stock", [
                'type' => 'out',
                'quantity' => 5,
                'notes' => 'Penjualan offline store.',
            ]);

        $responseOut->assertStatus(200)
            ->assertJsonPath('data.stock', 20);

        // Verify audit movements history
        $responseMovements = $this->actingAs($this->staff, 'sanctum')
            ->getJson("/api/v1/products/{$product->id}/stock-movements");

        $responseMovements->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_stock_adjustment_fails_when_deducting_more_than_available(): void
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'user_id' => $this->admin->id,
            'name' => 'Low Stock Product',
            'sku' => 'LOW-001',
            'slug' => 'low-stock-product',
            'price' => 100000,
            'stock' => 3,
        ]);

        $response = $this->actingAs($this->staff, 'sanctum')
            ->postJson("/api/v1/products/{$product->id}/adjust-stock", [
                'type' => 'out',
                'quantity' => 10,
                'notes' => 'Pengurangan melebihi stok.',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertEquals(3, $product->fresh()->stock);
    }

    public function test_user_can_soft_delete_product(): void
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'user_id' => $this->admin->id,
            'name' => 'To Be Deleted Product',
            'sku' => 'DEL-001',
            'slug' => 'to-be-deleted',
            'price' => 100000,
            'stock' => 5,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }
}
