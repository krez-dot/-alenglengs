<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_and_reports_are_admin_only(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/reports')->assertRedirect('/login');

        $cashier = User::factory()->create(['role' => 'cashier']);
        $this->actingAs($cashier)->get('/dashboard')->assertForbidden();
        $this->actingAs($cashier)->get('/reports')->assertForbidden();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/dashboard')->assertOk()->assertSee('Low Stock');
        $this->actingAs($admin)->get('/reports')->assertOk()->assertSee('Sales Report');
    }

    public function test_home_sends_each_role_to_its_landing_page(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/')->assertRedirect('/dashboard');
        $this->actingAs(User::factory()->create(['role' => 'cashier']))->get('/')->assertRedirect('/checkout');
    }

    public function test_dashboard_shows_todays_totals_and_low_stock(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cashier = User::factory()->create(['role' => 'cashier']);
        $category = Category::create(['name' => 'Snacks', 'color' => 'yellow']);
        $chips = $this->product($category, 'SNK-001', 'Chips', stock: 20);
        $this->product($category, 'SNK-002', 'Last Candy', stock: 1, reorder: 2);

        app(CheckoutService::class)->checkout([['product_id' => $chips->id, 'quantity' => 3]], 30, 'none', null, $cashier->id);

        $overview = $this->actingAs($admin)->get('/dashboard')->assertOk()->viewData('overview');

        $this->assertEqualsWithDelta(30.0, $overview['today_total'], 0.001);
        $this->assertSame(1, $overview['today_count']);
        $this->assertSame(18, $overview['total_stock']); // 17 chips + 1 candy
        $this->assertSame(['Last Candy'], $overview['low_stock']->pluck('name')->all());
        $this->assertSame('Chips', $overview['top_selling'][0]['name']);
        $this->assertCount(7, $overview['trend']);
        $this->assertEqualsWithDelta(30.0, $overview['trend'][6]['total'], 0.001);
    }

    public function test_report_totals_match_recorded_sales(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cashier = User::factory()->create(['role' => 'cashier', 'name' => 'Cora Cashier']);
        $category = Category::create(['name' => 'Snacks', 'color' => 'yellow']);
        $chips = $this->product($category, 'SNK-001', 'Chips', stock: 20, cost: 4, price: 10);

        $checkout = app(CheckoutService::class);
        $checkout->checkout([['product_id' => $chips->id, 'quantity' => 2]], 20, 'none', null, $cashier->id);      // 20.00
        $checkout->checkout([['product_id' => $chips->id, 'quantity' => 10]], 100, 'senior', 'SC-1', $cashier->id); // 100 - 20 = 80.00

        $today = now()->toDateString();
        $response = $this->actingAs($admin)->get("/reports?date_from={$today}&date_to={$today}")->assertOk();
        $report = $response->viewData('report');
        $summary = $report['summary'];

        $this->assertSame(2, $summary['sales_count']);
        $this->assertSame(12, $summary['items_sold']);
        $this->assertEqualsWithDelta(120.0, $summary['gross_sales'], 0.001);
        $this->assertEqualsWithDelta(20.0, $summary['discounts'], 0.001);
        $this->assertEqualsWithDelta(100.0, $summary['net_sales'], 0.001);
        $this->assertEqualsWithDelta(50.0, $summary['average_sale'], 0.001);
        $this->assertEqualsWithDelta(52.0, $summary['estimated_profit'], 0.001); // 100 net - 12 units * 4 cost

        $this->assertSame('Cora Cashier', $report['by_cashier'][0]['name']);
        $this->assertSame(2, $report['by_cashier'][0]['sales_count']);
        $this->assertSame('senior', $report['discount_summary'][0]['type']);
        $this->assertEqualsWithDelta(20.0, $report['discount_summary'][0]['amount'], 0.001);
        $this->assertSame('Snacks', $report['by_category'][0]['name']);
        $this->assertSame(12, $report['by_category'][0]['units']);
        $this->assertSame(8, $report['inventory']['units']);
    }

    public function test_report_only_counts_the_selected_dates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['name' => 'Snacks', 'color' => 'yellow']);
        $chips = $this->product($category, 'SNK-001', 'Chips', stock: 20);
        app(CheckoutService::class)->checkout([['product_id' => $chips->id, 'quantity' => 1]], 10);

        $report = $this->actingAs($admin)->get('/reports?date_from=2020-01-01&date_to=2020-01-03')
            ->assertOk()->viewData('report');

        $this->assertSame(0, $report['summary']['sales_count']);
        $this->assertCount(3, $report['daily']);
        $this->assertSame([], $report['top_products']);
    }

    public function test_report_rejects_an_end_date_before_the_start_date(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/reports?date_from=2026-02-10&date_to=2026-02-01')
            ->assertSessionHasErrors('date_to');
    }

    private function product(Category $category, string $sku, string $name, int $stock, int $reorder = 5, float $cost = 5, float $price = 10): Product
    {
        return Product::create([
            'sku' => $sku, 'name' => $name, 'category_id' => $category->id,
            'cost_price' => $cost, 'selling_price' => $price,
            'stock_quantity' => $stock, 'reorder_level' => $reorder,
        ]);
    }
}
