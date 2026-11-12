@extends('layouts.app')
@section('title', 'Overview')

@section('content')
@php $o = $overview; @endphp
<div class="page-header">
    <h1>Good day, {{ auth()->user()->name }} &#128075;</h1>
    <p>{{ now()->format('l, F j, Y') }} &middot; Quick summary of today's sales and current inventory status.</p>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-label">Today's Sales</div>
        <div class="stat-value green">&#8369;{{ number_format($o['today_total'], 2) }}</div>
        <div class="stat-sub">{{ $o['today_count'] }} transaction{{ $o['today_count'] === 1 ? '' : 's' }} today</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Transactions Today</div>
        <div class="stat-value">{{ $o['today_count'] }}</div>
        <div class="stat-sub">Completed sales</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Stock on Hand</div>
        <div class="stat-value">{{ number_format($o['total_stock']) }}</div>
        <div class="stat-sub">Units across {{ $o['product_count'] }} product{{ $o['product_count'] === 1 ? '' : 's' }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Low-Stock Products</div>
        <div class="stat-value">{{ $o['low_stock']->count() }}</div>
        <div class="stat-sub">At or below reorder level</div>
    </div>
</div>

<div class="card" style="margin-bottom:16px;">
    <div class="card-header-row"><strong>Sales &mdash; Last 7 Days</strong></div>
    <canvas id="salesTrendChart" height="90"></canvas>
</div>

<div class="dash-grid">
    <div class="card">
        <div class="card-header-row">
            <strong>Recent Transactions</strong>
            <a href="{{ route('sales.index') }}" class="link-accent">View all</a>
        </div>
        @if ($o['recent_sales']->isEmpty())
            <p class="empty-text">No sales recorded yet.</p>
        @else
            <ul class="simple-list">
                @foreach ($o['recent_sales'] as $sale)
                    <li>
                        <a href="{{ route('sales.show', $sale) }}">
                            <span>{{ $sale->reference_no }}</span>
                            <span class="price-cell">&#8369;{{ number_format((float) $sale->total_amount, 2) }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
    <div class="card">
        <div class="card-header-row">
            <strong>&#9888;&#65039; Low Stock</strong>
            <span class="badge-pill {{ $o['low_stock']->isEmpty() ? 'badge-ok' : 'badge-low' }}">{{ $o['low_stock']->count() }} item{{ $o['low_stock']->count() === 1 ? '' : 's' }}</span>
        </div>
        @if ($o['low_stock']->isEmpty())
            <p class="empty-text">All products are above their reorder level.</p>
        @else
            <ul class="simple-list">
                @foreach ($o['low_stock'] as $item)
                    <li>
                        <span>{{ $item->name }}</span>
                        <span class="badge-pill badge-low">{{ $item->stock_quantity }} left</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header-row"><strong>&#127942; Top-Selling Products</strong></div>
    @if (empty($o['top_selling']))
        <p class="empty-text">No sales recorded yet.</p>
    @else
        <ul class="simple-list">
            @foreach ($o['top_selling'] as $t)
                <li>
                    <span>{{ $t['name'] }}</span>
                    <span class="stat-sub">{{ $t['total_qty'] }} sold</span>
                </li>
            @endforeach
        </ul>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
var salesTrend = @json($o['trend']);
Chart.defaults.font.family = "'Poppins', sans-serif";
new Chart(document.getElementById('salesTrendChart'), {
    type: 'line',
    data: {
        labels: salesTrend.map(function (d) {
            return new Date(d.date + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }),
        datasets: [{
            label: 'Sales',
            data: salesTrend.map(function (d) { return d.total; }),
            borderColor: '#16a34a',
            backgroundColor: 'rgba(22, 163, 74, 0.12)',
            fill: true, tension: 0.35, pointBackgroundColor: '#16a34a', pointRadius: 4, borderWidth: 2
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
