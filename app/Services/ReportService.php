<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Sale;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

/**
 * Read-only sales and inventory analytics for the Overview dashboard and the
 * Reports page. Everything is computed in SQL from the live tables.
 */
class ReportService
{
    /** Dashboard data: today's numbers, 7-day trend, recent sales, low stock, top sellers. */
    public function overview(): array
    {
        $today = CarbonImmutable::today();
        $todaySales = fn () => Sale::query()->whereBetween('created_at', [$today->startOfDay(), $today->endOfDay()]);

        return [
            'today_total' => round((float) $todaySales()->sum('total_amount'), 2),
            'today_count' => $todaySales()->count(),
            'total_stock' => (int) Product::sum('stock_quantity'),
            'product_count' => Product::count(),
            'low_stock' => Product::with('category')->lowStock()->get(),
            'recent_sales' => Sale::latest('id')->limit(5)->get(),
            'top_selling' => $this->topProducts(null, null, 5),
            'trend' => $this->dailyTotals($today->subDays(6), $today),
        ];
    }

    /** Full report for an inclusive date range. Inventory figures are a current snapshot. */
    public function report(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $start = $from->startOfDay();
        $end = $to->endOfDay();

        $sales = Sale::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('COUNT(*) as sales_count, COALESCE(SUM(subtotal_amount), 0) as gross, COALESCE(SUM(discount_amount), 0) as discounts, COALESCE(SUM(total_amount), 0) as net')
            ->first();

        $lines = DB::table('sale_items as si')
            ->join('sales as s', 's.id', '=', 'si.sale_id')
            ->join('products as p', 'p.id', '=', 'si.product_id')
            ->whereBetween('s.created_at', [$start, $end])
            ->selectRaw('COALESCE(SUM(si.quantity), 0) as items_sold, COALESCE(SUM(si.quantity * p.cost_price), 0) as cost')
            ->first();

        $salesCount = (int) $sales->sales_count;
        $net = round((float) $sales->net, 2);

        $byCategory = DB::table('sale_items as si')
            ->join('sales as s', 's.id', '=', 'si.sale_id')
            ->join('products as p', 'p.id', '=', 'si.product_id')
            ->join('categories as c', 'c.id', '=', 'p.category_id')
            ->whereBetween('s.created_at', [$start, $end])
            ->groupBy('c.id', 'c.name')
            ->orderByDesc('revenue')
            ->get(['c.name', DB::raw('SUM(si.quantity) as units'), DB::raw('SUM(si.subtotal) as revenue')])
            ->map(fn ($r) => ['name' => $r->name, 'units' => (int) $r->units, 'revenue' => round((float) $r->revenue, 2)])
            ->all();

        $byCashier = DB::table('sales as s')
            ->leftJoin('users as u', 'u.id', '=', 's.user_id')
            ->whereBetween('s.created_at', [$start, $end])
            ->groupBy('u.id', 'u.name')
            ->orderByDesc('total')
            ->get([DB::raw("COALESCE(u.name, 'Unassigned') as name"), DB::raw('COUNT(*) as sales_count'), DB::raw('SUM(s.total_amount) as total')])
            ->map(fn ($r) => ['name' => $r->name, 'sales_count' => (int) $r->sales_count, 'total' => round((float) $r->total, 2)])
            ->all();

        $discounts = Sale::query()
            ->whereBetween('created_at', [$start, $end])
            ->where('discount_type', '!=', 'none')
            ->groupBy('discount_type')
            ->selectRaw('discount_type, COUNT(*) as sales_count, SUM(discount_amount) as amount')
            ->get()
            ->map(fn ($r) => ['type' => $r->discount_type, 'sales_count' => (int) $r->sales_count, 'amount' => round((float) $r->amount, 2)])
            ->all();

        $inventory = Product::query()
            ->selectRaw('COUNT(*) as products, COALESCE(SUM(stock_quantity), 0) as units, COALESCE(SUM(stock_quantity * cost_price), 0) as cost_value, COALESCE(SUM(stock_quantity * selling_price), 0) as retail_value, COALESCE(SUM(CASE WHEN stock_quantity <= 0 THEN 1 ELSE 0 END), 0) as out_of_stock')
            ->first();

        return [
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'summary' => [
                'sales_count' => $salesCount,
                'items_sold' => (int) $lines->items_sold,
                'gross_sales' => round((float) $sales->gross, 2),
                'discounts' => round((float) $sales->discounts, 2),
                'net_sales' => $net,
                'average_sale' => $salesCount > 0 ? round($net / $salesCount, 2) : 0.0,
                // Uses each product's CURRENT cost price, so it is an estimate.
                'estimated_profit' => round($net - (float) $lines->cost, 2),
            ],
            'daily' => $this->dailyTotals($from, $to),
            'top_products' => $this->topProducts($from, $to, 10),
            'by_category' => $byCategory,
            'by_cashier' => $byCashier,
            'discount_summary' => $discounts,
            'inventory' => [
                'products' => (int) $inventory->products,
                'units' => (int) $inventory->units,
                'cost_value' => round((float) $inventory->cost_value, 2),
                'retail_value' => round((float) $inventory->retail_value, 2),
                'out_of_stock' => (int) $inventory->out_of_stock,
                'low_stock' => Product::with('category')->lowStock()->get()
                    ->map(fn ($p) => [
                        'name' => $p->name,
                        'category' => $p->category?->name,
                        'stock' => $p->stock_quantity,
                        'reorder' => $p->reorder_level,
                    ])->all(),
            ],
        ];
    }

    /**
     * One row per day in the range (days without sales are zero-filled so charts have no gaps).
     *
     * @return array<int, array{date: string, total: float, sales_count: int}>
     */
    public function dailyTotals(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $rows = Sale::query()
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->selectRaw('DATE(created_at) as day, SUM(total_amount) as total, COUNT(*) as sales_count')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $series = [];
        foreach (CarbonPeriod::create($from->startOfDay(), $to->startOfDay()) as $date) {
            $key = $date->format('Y-m-d');
            $row = $rows->get($key);
            $series[] = [
                'date' => $key,
                'total' => $row ? round((float) $row->total, 2) : 0.0,
                'sales_count' => $row ? (int) $row->sales_count : 0,
            ];
        }

        return $series;
    }

    /**
     * Best sellers by units, optionally limited to a date range.
     *
     * @return array<int, array{id: int, name: string, total_qty: int, revenue: float}>
     */
    public function topProducts(?CarbonImmutable $from, ?CarbonImmutable $to, int $limit = 5): array
    {
        return DB::table('sale_items as si')
            ->join('sales as s', 's.id', '=', 'si.sale_id')
            ->join('products as p', 'p.id', '=', 'si.product_id')
            ->when($from, fn ($q) => $q->where('s.created_at', '>=', $from->startOfDay()))
            ->when($to, fn ($q) => $q->where('s.created_at', '<=', $to->endOfDay()))
            ->groupBy('p.id', 'p.name')
            ->orderByDesc('total_qty')
            ->orderBy('p.name')
            ->limit($limit)
            ->get(['p.id', 'p.name', DB::raw('SUM(si.quantity) as total_qty'), DB::raw('SUM(si.subtotal) as revenue')])
            ->map(fn ($r) => [
                'id' => (int) $r->id,
                'name' => $r->name,
                'total_qty' => (int) $r->total_qty,
                'revenue' => round((float) $r->revenue, 2),
            ])
            ->all();
    }
}
