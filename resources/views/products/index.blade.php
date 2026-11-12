@extends('layouts.app')
@section('title', 'Products')

@section('content')
@php use App\Support\Ui; @endphp
<div class="page-header page-header-row">
    <div>
        <h1>Products</h1>
        <p>Manage your catalog &mdash; add, edit, or remove products.</p>
    </div>
    <a href="{{ route('products.create') }}" class="btn btn-primary">+ Add Product</a>
</div>

<form method="get" class="toolbar">
    <div class="search-input-wrap">
        <span class="search-icon">{{ Ui::navIcon('search') }}</span>
        <input type="text" name="q" class="search-input" placeholder="Search name or SKU..." value="{{ $keyword }}">
    </div>
    <div class="pill-group">
        <button type="submit" name="category_id" value="" class="pill {{ $categoryId === null ? 'active' : '' }}">All</button>
        @foreach ($categories as $cat)
            <button type="submit" name="category_id" value="{{ $cat->id }}" class="pill {{ $categoryId === $cat->id ? 'active' : '' }}">{{ $cat->name }}</button>
        @endforeach
    </div>
    <select name="supplier_id" class="sort-select" onchange="this.form.submit()">
        <option value="">All Suppliers</option>
        @foreach ($suppliers as $sup)
            <option value="{{ $sup->id }}" @selected($supplierId === $sup->id)>{{ $sup->name }}</option>
        @endforeach
    </select>
    <div class="sort-control-group">
        <select name="sort" class="sort-select" onchange="this.form.submit()">
            <option value="name" @selected($sort === 'name')>Sort: Name</option>
            <option value="selling_price" @selected($sort === 'selling_price')>Sort: Price</option>
            <option value="stock_quantity" @selected($sort === 'stock_quantity')>Sort: Stock</option>
        </select>
        <button type="submit" name="dir" value="{{ $dir === 'asc' ? 'desc' : 'asc' }}" class="sort-dir-toggle" title="Toggle sort direction">
            {!! $dir === 'asc' ? '&uarr; Asc' : '&darr; Desc' !!}
        </button>
    </div>
</form>

@if ($categories->isNotEmpty())
    <div class="category-legend">
        <span class="category-legend-label">Categories:</span>
        @foreach ($categories as $cat)
            <span class="badge-pill {{ Ui::categoryBadgeClass($cat->name, $cat->color) }}">{{ $cat->name }}</span>
        @endforeach
    </div>
@endif

<div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr><th>SKU</th><th>Product</th><th>Category</th><th>Supplier</th><th>Price</th><th>Stock</th><th>Actions</th></tr>
        </thead>
        <tbody>
            @forelse ($products as $product)
                @php $isLow = $product->stock_quantity <= $product->reorder_level; @endphp
                <tr>
                    <td class="muted">{{ $product->sku }}</td>
                    <td class="strong">{{ $product->name }}</td>
                    <td><span class="badge-pill {{ Ui::categoryBadgeClass($product->category?->name, $product->category?->color) }}">{{ $product->category?->name ?? '-' }}</span></td>
                    <td class="muted">{{ $product->supplier?->name ?? '-' }}</td>
                    <td class="price-cell">&#8369;{{ number_format((float) $product->selling_price, 2) }}</td>
                    <td>
                        {{ $product->stock_quantity }} units
                        <span class="badge-pill {{ $isLow ? 'badge-low' : 'badge-ok' }}">{{ $isLow ? 'Low' : 'OK' }}</span>
                    </td>
                    <td class="actions-cell">
                        <button type="button" class="btn btn-outline btn-sm restock-btn"
                                data-action="{{ route('products.restock', $product) }}"
                                data-name="{{ $product->name }}">Restock</button>
                        <a href="{{ route('products.edit', $product) }}" class="btn btn-outline btn-sm">Edit</a>
                        <form action="{{ route('products.destroy', $product) }}" method="post" class="inline-form" onsubmit="return confirm('Delete this product?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr class="empty-row"><td colspan="7">No products found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $products->links('pagination.aling') }}

<div class="modal-overlay" id="restockModal">
    <div class="modal-box">
        <h3>Restock Product</h3>
        <p class="modal-sub" id="restockProductName">Add stock for this product.</p>
        <form method="post" id="restockForm" action="">
            @csrf
            <div class="field">
                <label>Quantity to Add</label>
                <input type="number" name="quantity" id="restockQuantity" min="1" value="10" required>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline btn-block" id="restockCancel">Cancel</button>
                <button type="submit" class="btn btn-primary btn-block">Add Stock</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var modal = document.getElementById('restockModal');
    var form = document.getElementById('restockForm');
    var nameLabel = document.getElementById('restockProductName');
    var qtyInput = document.getElementById('restockQuantity');

    document.querySelectorAll('.restock-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            form.action = btn.dataset.action;
            nameLabel.textContent = 'Add stock for "' + btn.dataset.name + '".';
            qtyInput.value = 10;
            modal.classList.add('open');
            qtyInput.focus();
        });
    });
    document.getElementById('restockCancel').addEventListener('click', function () {
        modal.classList.remove('open');
    });
})();
</script>
@endpush
