@extends('layouts.app')
@section('title', 'Sales History')

@section('content')
<div class="page-header">
    <h1>Sales History</h1>
    <p>Every completed transaction. Click a row to view its receipt.</p>
</div>

<form method="get" class="filters-bar">
    <div class="field">
        <label>Search</label>
        <input type="text" name="q" placeholder="Reference No." value="{{ $keyword }}">
    </div>
    <div class="field">
        <label>From</label>
        <input type="date" name="date_from" value="{{ $dateFrom }}">
    </div>
    <div class="field">
        <label>To</label>
        <input type="date" name="date_to" value="{{ $dateTo }}">
    </div>
    <div class="field">
        <label>Order</label>
        <select name="order">
            <option value="desc" @selected($order === 'desc')>Newest first</option>
            <option value="asc" @selected($order === 'asc')>Oldest first</option>
        </select>
    </div>
    <div class="filters-actions">
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="{{ route('sales.index') }}" class="btn btn-outline">Reset</a>
    </div>
</form>

<div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr><th>Reference No.</th><th>Date</th><th>Customer</th><th>Cashier</th><th>Total</th><th>Paid</th><th>Change</th></tr>
        </thead>
        <tbody>
            @forelse ($sales as $sale)
                <tr class="clickable-row" onclick="window.location='{{ route('sales.show', $sale) }}'">
                    <td class="strong">
                        {{ $sale->reference_no }}
                        @if ($sale->discount_type !== 'none')
                            <span class="badge-pill badge-purple">{{ $sale->discount_type === 'pwd' ? 'PWD' : 'SC' }} -20%</span>
                        @endif
                    </td>
                    <td class="muted">{{ $sale->created_at->format('M d, Y h:i A') }}</td>
                    <td>Walk-in</td>
                    <td>Admin</td>
                    <td class="price-cell">&#8369;{{ number_format((float) $sale->total_amount, 2) }}</td>
                    <td>&#8369;{{ number_format((float) $sale->amount_paid, 2) }}</td>
                    <td>&#8369;{{ number_format((float) $sale->change_due, 2) }}</td>
                </tr>
            @empty
                <tr class="empty-row"><td colspan="7">No sales transactions found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $sales->links('pagination.aling') }}
@endsection
