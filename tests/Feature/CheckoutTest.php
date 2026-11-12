<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cashier = User::factory()->create(['role' => 'cashier']);
        $this->category = Category::create(['name' => 'Beverages', 'color' => 'blue']);
    }

    public function test_sale_saves_totals_cashier_and_a_stock_movement(): void
    {
        $coke = $this->product('BEV-001', 'Coke 1.5L', stock: 24, price: 65);

        $response = $this->actingAs($this->cashier)->post('/checkout', [
            'items' => [['product_id' => $coke->id, 'quantity' => 2]],
            'amount_paid' => 200,
        ]);

        $sale = Sale::firstOrFail();
        $response->assertRedirect(route('sales.show', $sale));
        $this->assertSame('130.00', $sale->subtotal_amount);
        $this->assertSame('130.00', $sale->total_amount);
        $this->assertSame('70.00', $sale->change_due);
        $this->assertSame($this->cashier->id, $sale->user_id);
        $this->assertSame(22, $coke->fresh()->stock_quantity);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $coke->id, 'movement_type' => 'out', 'quantity' => 2]);
    }

    public function test_server_ignores_prices_sent_by_the_browser(): void
    {
        $coke = $this->product('BEV-001', 'Coke 1.5L', stock: 24, price: 65);

        $this->actingAs($this->cashier)->post('/checkout', [
            'items' => [['product_id' => $coke->id, 'quantity' => 1, 'unit_price' => 1]],
            'amount_paid' => 100,
        ]);

        $this->assertSame('65.00', Sale::firstOrFail()->total_amount);
        $this->assertSame('65.00', SaleItem::firstOrFail()->unit_price);
    }

    public function test_senior_discount_is_twenty_percent_and_saves_the_id_number(): void
    {
        $coke = $this->product('BEV-001', 'Coke 1.5L', stock: 24, price: 50);

        $this->actingAs($this->cashier)->post('/checkout', [
            'items' => [['product_id' => $coke->id, 'quantity' => 2]], // subtotal 100
            'amount_paid' => 100,
            'discount_type' => 'senior',
            'discount_id_number' => 'SC-123456',
            'discount_confirmed' => '1',
        ]);

        $sale = Sale::firstOrFail();
        $this->assertSame('senior', $sale->discount_type);
        $this->assertSame('SC-123456', $sale->discount_id_number);
        $this->assertSame('20.00', $sale->discount_amount);
        $this->assertSame('80.00', $sale->total_amount);
        $this->assertSame('20.00', $sale->change_due);
    }

    public function test_discount_needs_a_confirmed_id_number(): void
    {
        $coke = $this->product('BEV-001', 'Coke 1.5L', stock: 24, price: 50);
        $items = [['product_id' => $coke->id, 'quantity' => 1]];

        $this->actingAs($this->cashier)->post('/checkout', [
            'items' => $items, 'amount_paid' => 50,
            'discount_type' => 'pwd', 'discount_id_number' => 'PWD-9',
        ])->assertSessionHas('danger'); // ID not confirmed

        $this->actingAs($this->cashier)->post('/checkout', [
            'items' => $items, 'amount_paid' => 50,
            'discount_type' => 'pwd', 'discount_confirmed' => '1',
        ])->assertSessionHas('danger'); // no ID number

        $this->assertSame(0, Sale::count());
        $this->assertSame(24, $coke->fresh()->stock_quantity);
    }

    public function test_a_failed_line_rolls_back_the_whole_sale(): void
    {
        $plenty = $this->product('BEV-001', 'Plenty', stock: 50, price: 10);
        $scarce = $this->product('BEV-002', 'Scarce', stock: 1, price: 10);

        $this->actingAs($this->cashier)->post('/checkout', [
            'items' => [
                ['product_id' => $plenty->id, 'quantity' => 5],
                ['product_id' => $scarce->id, 'quantity' => 2],
            ],
            'amount_paid' => 500,
        ])->assertSessionHas('danger');

        $this->assertSame(0, Sale::count());
        $this->assertSame(0, StockMovement::count());
        $this->assertSame(50, $plenty->fresh()->stock_quantity);
        $this->assertSame(1, $scarce->fresh()->stock_quantity);
    }

    public function test_a_failure_after_writing_has_started_rolls_everything_back(): void
    {
        $a = $this->product('BEV-001', 'Alpha', stock: 10, price: 10);
        $b = $this->product('BEV-002', 'Bravo', stock: 10, price: 10);

        // Simulate the database failing while the second line's stock movement is written,
        // i.e. AFTER the sale, its first line and the first stock deduction already happened.
        $movements = 0;
        StockMovement::creating(function () use (&$movements) {
            if (++$movements === 2) {
                throw new \RuntimeException('simulated database failure');
            }
        });

        try {
            app(CheckoutService::class)->checkout([
                ['product_id' => $a->id, 'quantity' => 3],
                ['product_id' => $b->id, 'quantity' => 4],
            ], 500, 'none', null, $this->cashier->id);
            $this->fail('The simulated failure should have aborted the sale.');
        } catch (\RuntimeException $e) {
            $this->assertSame('simulated database failure', $e->getMessage());
        }

        $this->assertSame(0, Sale::count());
        $this->assertSame(0, SaleItem::count());
        $this->assertSame(0, StockMovement::count());
        $this->assertSame(10, $a->fresh()->stock_quantity);
        $this->assertSame(10, $b->fresh()->stock_quantity);
    }

    public function test_payment_below_the_total_is_refused(): void
    {
        $coke = $this->product('BEV-001', 'Coke 1.5L', stock: 24, price: 65);

        $this->actingAs($this->cashier)->post('/checkout', [
            'items' => [['product_id' => $coke->id, 'quantity' => 2]],
            'amount_paid' => 100, // total is 130
        ])->assertSessionHas('danger');

        $this->assertSame(0, Sale::count());
        $this->assertSame(24, $coke->fresh()->stock_quantity);
    }

    public function test_the_same_product_on_two_lines_is_combined(): void
    {
        $coke = $this->product('BEV-001', 'Coke 1.5L', stock: 3, price: 10);

        $this->actingAs($this->cashier)->post('/checkout', [
            'items' => [
                ['product_id' => $coke->id, 'quantity' => 2],
                ['product_id' => $coke->id, 'quantity' => 2], // 4 > 3 in stock
            ],
            'amount_paid' => 100,
        ])->assertSessionHas('danger');

        $this->assertSame(0, Sale::count());
        $this->assertSame(3, $coke->fresh()->stock_quantity);
    }

    private function product(string $sku, string $name, int $stock, float $price): Product
    {
        return Product::create([
            'sku' => $sku, 'name' => $name, 'category_id' => $this->category->id,
            'cost_price' => 1, 'selling_price' => $price,
            'stock_quantity' => $stock, 'reorder_level' => 5,
        ]);
    }
}
