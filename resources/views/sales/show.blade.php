@extends('layouts.app')
@section('title', 'Sale Receipt')

@section('content')
<div class="page-header">
    <h1>Receipt: {{ $sale->reference_no }}</h1>
    <p>{{ $sale->created_at->format('M d, Y h:i A') }}</p>
</div>

<div id="printableReceipt">
    <div class="print-only">
        <strong style="font-size:1.1rem;">Aling Leng's Sari-Sari Store</strong><br>
        Receipt: {{ $sale->reference_no }}<br>
        {{ $sale->created_at->format('M d, Y h:i A') }}
    </div>
    <div class="card" style="max-width:520px;">
        <table class="data-table">
            <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead>
            <tbody>
                @foreach ($sale->items as $item)
                    <tr>
                        <td>{{ $item->product->name }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>&#8369;{{ number_format((float) $item->unit_price, 2) }}</td>
                        <td class="price-cell">&#8369;{{ number_format((float) $item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="receipt-summary">
            <div class="order-row"><span>Subtotal</span><span>&#8369;{{ number_format((float) $sale->subtotal_amount, 2) }}</span></div>
            @if ($sale->discount_type !== 'none')
                <div class="order-row" style="color:var(--red-600);">
                    <span>{{ $sale->discount_type === 'pwd' ? 'PWD' : 'Senior Citizen' }} Discount (&minus;{{ \App\Models\Sale::DISCOUNT_PERCENT }}%) &mdash; ID {{ $sale->discount_id_number }}</span>
                    <span>&minus;&#8369;{{ number_format((float) $sale->discount_amount, 2) }}</span>
                </div>
            @endif
            <div class="order-row"><span>Total</span><strong>&#8369;{{ number_format((float) $sale->total_amount, 2) }}</strong></div>
            <div class="order-row"><span>Amount Paid</span><span>&#8369;{{ number_format((float) $sale->amount_paid, 2) }}</span></div>
            <div class="order-row"><span>Change</span><span>&#8369;{{ number_format((float) $sale->change_due, 2) }}</span></div>
        </div>
    </div>
</div>

<div class="no-print" style="margin-top:16px; display:flex; gap:10px;">
    <button type="button" class="btn btn-primary" onclick="window.print()">Print Receipt</button>
    <a href="{{ route('sales.index') }}" class="btn btn-outline">&larr; Back to Sales History</a>
</div>
@endsection
