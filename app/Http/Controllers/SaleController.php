<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function index(Request $request): View
    {
        $keyword = trim((string) $request->query('q', ''));
        $dateFrom = (string) $request->query('date_from', '');
        $dateTo = (string) $request->query('date_to', '');
        $order = strtolower((string) $request->query('order', 'desc')) === 'asc' ? 'asc' : 'desc';

        $sales = Sale::query()
            ->when($keyword !== '', fn ($q) => $q->where('reference_no', 'like', "%{$keyword}%"))
            ->when($dateFrom !== '', fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->orderBy('created_at', $order)
            ->orderBy('id', $order)
            ->paginate(15)
            ->withQueryString();

        return view('sales.index', compact('sales', 'keyword', 'dateFrom', 'dateTo', 'order'));
    }

    public function show(Sale $sale): View
    {
        $sale->load('items.product');

        return view('sales.show', ['sale' => $sale]);
    }
}
