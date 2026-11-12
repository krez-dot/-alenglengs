@extends('layouts.app')
@section('title', 'Sales Report')

@section('content')
@php
    $r = $report;
    $s = $r['summary'];
    $inv = $r['inventory'];
    $peso = fn ($n) => '₱' . number_format((float) $n, 2);
    $today = now()->toDateString();
    $presets = [
        'Today' => [$today, $today],
        'Last 7 days' => [now()->subDays(6)->toDateString(), $today],
        'Last 30 days' => [now()->subDays(29)->toDateString(), $today],
        'This month' => [now()->startOfMonth()->toDateString(), $today],
    ];
@endphp
<div class="page-header page-header-row">
    <div>
        <h1>Sales Report</h1>
        <p>{{ \Carbon\Carbon::parse($r['range']['from'])->format('M d, Y') }} &ndash; {{ \Carbon\Carbon::parse($r['range']['to'])->format('M d, Y') }} &middot; Sales, discounts, best sellers, and inventory health.</p>
    </div>
    <button type="button" class="btn btn-outline no-print" onclick="window.print()">Print Report</button>
</div>

<form method="get" class="filters-bar no-print">
    <div class="field">
        <label>From</label>
        <input type="date" name="date_from" value="{{ $r['range']['from'] }}">
    </div>
    <div class="field">
        <label>To</label>
        <input type="date" name="date_to" value="{{ $r['range']['to'] }}">
    </div>
    <div class="filters-actions">
        <button type="submit" class="btn btn-primary">Apply</button>
        @foreach ($presets as $label => [$pf, $pt])
            <a href="{{ route('reports.index', ['date_from' => $pf, 'date_to' => $pt]) }}" class="btn btn-outline">{{ $label }}</a>
        @endforeach
    </div>
</form>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-label">Net Sales</div>
        <div class="stat-value green">{{ $peso($s['net_sales']) }}</div>
        <div class="stat-sub">After discounts</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Transactions</div>
        <div class="stat-value">{{ number_format($s['sales_count']) }}</div>
        <div class="stat-sub">Avg. {{ $peso($s['average_sale']) }} per sale</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Items Sold</div>
        <div class="stat-value">{{ number_format($s['items_sold']) }}</div>
        <div class="stat-sub">Units</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Estimated Profit</div>
        <div class="stat-value">{{ $peso($s['estimated_profit']) }}</div>
        <div class="stat-sub">Net sales minus cost (current cost prices)</div>
    </div>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-label">Gross Sales</div>
        <div class="stat-value">{{ $peso($s['gross_sales']) }}</div>
        <div class="stat-sub">Before discounts</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Discounts Given</div>
        <div class="stat-value">{{ $peso($s['discounts']) }}</div>
        <div class="stat-sub">Senior Citizen / PWD</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Stock Value (at cost)</div>
        <div class="stat-value">{{ $peso($inv['cost_value']) }}</div>
        <div class="stat-sub">{{ number_format($inv['units']) }} units on hand now</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Out of Stock</div>
        <div class="stat-value">{{ $inv['out_of_stock'] }}</div>
        <div class="stat-sub">of {{ $inv['products'] }} products</div>
    </div>
</div>

<div class="card" style="margin-bottom:16px;">
    <div class="card-header-row"><strong>Daily Sales</strong></div>
    <canvas id="dailyChart" height="90"></canvas>
</div>

<div class="dash-grid">
    <div class="card">
        <div class="card-header-row"><strong>&#127942; Top Products</strong><span class="stat-sub">by units sold</span></div>
        @if (empty($r['top_products']))
            <p class="empty-text">No sales in this period.</p>
        @else
            <table class="data-table">
                <thead><tr><th>Product</th><th>Units</th><th>Revenue</th></tr></thead>
                <tbody>
                    @foreach ($r['top_products'] as $t)
                        <tr><td>{{ $t['name'] }}</td><td>{{ $t['total_qty'] }}</td><td class="price-cell">{{ $peso($t['revenue']) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
    <div class="card">
        <div class="card-header-row"><strong>Sales by Category</strong><span class="stat-sub">before discounts</span></div>
        @if (empty($r['by_category']))
            <p class="empty-text">No sales in this period.</p>
        @else
            <table class="data-table">
                <thead><tr><th>Category</th><th>Units</th><th>Revenue</th></tr></thead>
                <tbody>
                    @foreach ($r['by_category'] as $c)
                        <tr><td>{{ $c['name'] }}</td><td>{{ $c['units'] }}</td><td class="price-cell">{{ $peso($c['revenue']) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

<div class="dash-grid">
    <div class="card">
        <div class="card-header-row"><strong>Sales by Cashier</strong></div>
        @if (empty($r['by_cashier']))
            <p class="empty-text">No sales in this period.</p>
        @else
            <table class="data-table">
                <thead><tr><th>Cashier</th><th>Sales</th><th>Total</th></tr></thead>
                <tbody>
                    @foreach ($r['by_cashier'] as $c)
                        <tr><td>{{ $c['name'] }}</td><td>{{ $c['sales_count'] }}</td><td class="price-cell">{{ $peso($c['total']) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
    <div class="card">
        <div class="card-header-row"><strong>Discounts</strong></div>
        @if (empty($r['discount_summary']))
            <p class="empty-text">No discounted sales in this period.</p>
        @else
            <table class="data-table">
                <thead><tr><th>Type</th><th>Sales</th><th>Amount</th></tr></thead>
                <tbody>
                    @foreach ($r['discount_summary'] as $d)
                        <tr><td>{{ $d['type'] === 'pwd' ? 'PWD' : 'Senior Citizen' }}</td><td>{{ $d['sales_count'] }}</td><td class="price-cell">{{ $peso($d['amount']) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header-row">
        <strong>&#9888;&#65039; Inventory: Low Stock (current)</strong>
        <span class="badge-pill {{ empty($inv['low_stock']) ? 'badge-ok' : 'badge-low' }}">{{ count($inv['low_stock']) }} item{{ count($inv['low_stock']) === 1 ? '' : 's' }}</span>
    </div>
    @if (empty($inv['low_stock']))
        <p class="empty-text">All products are above their reorder level.</p>
    @else
        <table class="data-table">
            <thead><tr><th>Product</th><th>Category</th><th>In stock</th><th>Reorder level</th></tr></thead>
            <tbody>
                @foreach ($inv['low_stock'] as $p)
                    <tr><td>{{ $p['name'] }}</td><td>{{ $p['category'] ?? '-' }}</td><td><span class="badge-pill badge-low">{{ $p['stock'] }}</span></td><td>{{ $p['reorder'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
var daily = @json($r['daily']);
Chart.defaults.font.family = "'Poppins', sans-serif";
new Chart(document.getElementById('dailyChart'), {
    type: 'bar',
    data: {
        labels: daily.map(function (d) {
            return new Date(d.date + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }),
        datasets: [{
            label: 'Sales',
            data: daily.map(function (d) { return d.total; }),
            backgroundColor: 'rgba(22, 163, 74, 0.75)',
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: function (ctx) { return '₱' + ctx.parsed.y.toFixed(2); } } }
        },
        scales: {
            y: { beginAtZero: true, ticks: { callback: function (v) { return '₱' + v; } }, grid: { color: '#f1efe9' } },
            x: { grid: { display: false } }
        }
    }
});
</script>
@endpush
