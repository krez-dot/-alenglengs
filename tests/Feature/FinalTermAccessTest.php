<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinalTermAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_pages_require_authentication_and_cashiers_are_limited_to_pos(): void
    {
        $this->get('/products')->assertRedirect('/login');

        $cashier = User::factory()->create(['role' => 'cashier']);
        $this->actingAs($cashier)->get('/products')->assertForbidden();
        $this->actingAs($cashier)->get('/checkout')->assertOk();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/products')->assertOk();
    }

    public function test_users_can_sign_in_and_sign_out(): void
    {
        $user = User::factory()->create(['password' => 'a-strong-password']);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'a-strong-password',
        ])->assertRedirect('/checkout');

        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_cashier_api_token_can_read_products_but_cannot_manage_them(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $category = Category::create(['name' => 'Snacks', 'color' => 'yellow']);
        $this->createProduct($category);
        $this->getJson('/api/products')->assertUnauthorized();

        $tokenResponse = $this->postJson('/api/auth/token', [
            'email' => $cashier->email,
            'password' => 'password',
            'token_name' => 'postman',
        ])->assertCreated();

        $token = $tokenResponse->json('token');
        $this->withToken($token)->getJson('/api/products')->assertOk()->assertJsonPath('data.0.sku', 'SNK-001');
        $this->withToken($token)->postJson('/api/products', [])->assertForbidden();
    }

    public function test_admin_api_token_can_create_and_update_products(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['name' => 'Beverages', 'color' => 'blue']);
        $token = $admin->createToken('admin', ['*'])->plainTextToken;
        $payload = [
            'sku' => 'BEV-001',
            'name' => 'Bottled Water',
            'category_id' => $category->id,
            'cost_price' => 5,
            'selling_price' => 10,
            'stock_quantity' => 20,
            'reorder_level' => 5,
        ];

        $created = $this->withToken($token)->postJson('/api/products', $payload)
            ->assertCreated()
            ->assertJsonPath('data.name', 'Bottled Water');

        $this->withToken($token)->patchJson('/api/products/'.$created->json('data.id'), [
            'selling_price' => 12,
        ])->assertOk()->assertJsonPath('data.selling_price', '12.00');
    }

    public function test_api_checkout_records_cashier_and_updates_inventory(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $category = Category::create(['name' => 'Snacks', 'color' => 'yellow']);
        $product = $this->createProduct($category);
        $token = $cashier->createToken('test', ['sales:create', 'sales:read', 'products:read'])->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/sales', [
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'amount_paid' => 20,
        ])->assertCreated()
            ->assertJsonPath('data.items.0.quantity', 2)
            ->assertJsonPath('data.cashier.id', $cashier->id);

        $this->assertDatabaseHas('sales', [
            'id' => $response->json('data.id'),
            'user_id' => $cashier->id,
            'total_amount' => '20.00',
        ]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 8]);
    }

    private function createProduct(Category $category): Product
    {
        return Product::create([
            'sku' => 'SNK-001',
            'name' => 'Test Snack',
            'category_id' => $category->id,
            'cost_price' => 5,
            'selling_price' => 10,
            'stock_quantity' => 10,
            'reorder_level' => 2,
        ]);
    }
}
