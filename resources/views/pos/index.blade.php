@extends('layouts.app')
@section('title', 'Checkout')

@section('content')
@php use App\Support\Ui; @endphp
<div class="pos-layout">
    <div class="pos-main">
        <div class="search-input-wrap" style="margin-bottom:16px;">
            <span class="search-icon">{{ Ui::navIcon('search') }}</span>
            <input type="text" id="posSearch" class="search-input" placeholder="Search products or SKU...">
        </div>
        <div class="pill-group" id="posCategoryPills" style="margin-bottom:20px;">
            <button type="button" class="pill active" data-category="all">All</button>
            @foreach ($categories as $cat)
                <button type="button" class="pill" data-category="{{ $cat->name }}">{{ $cat->name }}</button>
            @endforeach
        </div>

        @if ($categories->isNotEmpty())
            <div class="category-legend">
                <span class="category-legend-label">Categories:</span>
                @foreach ($categories as $cat)
                    <span class="badge-pill {{ Ui::categoryBadgeClass($cat->name, $cat->color) }}">{{ $cat->name }}</span>
                @endforeach
            </div>
        @endif

        @if ($products->isEmpty())
            <p class="empty-text">No products available. Add products first.</p>
        @else
            <div class="product-grid" id="posProductGrid">
                @foreach ($products as $p)
                    <button type="button" class="product-card"
                        data-id="{{ $p->id }}"
                        data-name="{{ $p->name }}"
                        data-price="{{ (float) $p->selling_price }}"
                        data-stock="{{ $p->stock_quantity }}"
                        data-category="{{ $p->category?->name }}"
                        data-search="{{ strtolower($p->name . ' ' . $p->sku) }}"
                        @disabled($p->stock_quantity < 1)>
                        <span class="badge-pill {{ Ui::categoryBadgeClass($p->category?->name, $p->category?->color) }}">{{ $p->category?->name ?? 'Uncategorized' }}</span>
                        <div class="product-name">{{ $p->name }}</div>
                        <div class="product-price">&#8369;{{ number_format((float) $p->selling_price, 2) }}</div>
                        <div class="product-stock">{{ $p->stock_quantity }} in stock</div>
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    <aside class="order-panel">
        <div class="order-title">Order</div>
        <div class="order-empty" id="orderEmpty">
            {{ Ui::navIcon('cart') }}
            <strong>Cart is empty</strong>
            <span>Tap a product to add it</span>
        </div>
        <div class="order-items" id="cartBody"></div>

        <form method="post" action="{{ route('pos.store') }}" id="checkoutForm">
            @csrf
            <div id="hiddenItems"></div>
            <div class="order-summary">
                <div class="order-row"><span>Subtotal</span><span>&#8369;<span id="cartSubtotal">0.00</span></span></div>

                <label class="discount-toggle">
                    <span>Senior Citizen / PWD Discount</span>
                    <input type="checkbox" id="discountToggle">
                </label>
                <div class="discount-fields" id="discountFields">
                    <div class="field">
                        <label>Type</label>
                        <select id="discountType" name="discount_type" disabled>
                            <option value="senior">Senior Citizen</option>
                            <option value="pwd">PWD</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>ID Number</label>
                        <input type="text" id="discountIdNumber" name="discount_id_number" maxlength="50" placeholder="SC / PWD ID no." disabled>
                    </div>
                    <label class="confirm-check">
                        <input type="checkbox" id="discountConfirmed" name="discount_confirmed" value="1" disabled>
                        <span>ID presented and verified</span>
                    </label>
                </div>

                <div class="order-row discount-row" id="discountRow"><span>Discount (&minus;{{ \App\Models\Sale::DISCOUNT_PERCENT }}%)</span><span>&minus;&#8369;<span id="discountAmountDisplay">0.00</span></span></div>

                <div class="order-total-row"><span>Total</span><span>&#8369;<span id="cartTotal">0.00</span></span></div>
                <div class="field">
                    <label>Cash Tendered</label>
                    <input type="number" step="0.01" min="0" name="amount_paid" id="amountPaid" placeholder="0.00">
                </div>
                <div class="order-row"><span>Change</span><span>&#8369;<span id="changeDue">0.00</span></span></div>
                <button type="button" class="btn btn-primary btn-block" id="checkoutBtn" disabled>Add items to checkout</button>
            </div>
        </form>
    </aside>
</div>

<div class="modal-overlay" id="confirmModal">
    <div class="modal-box">
        <h3>Confirm Sale</h3>
        <p class="modal-sub">Review the order before completing the transaction.</p>
        <div class="order-items" id="modalItems" style="display:flex;max-height:220px;"></div>
        <div class="modal-summary">
            <div class="order-row"><span>Subtotal</span><span>&#8369;<span id="modalSubtotal">0.00</span></span></div>
            <div class="order-row discount-row" id="modalDiscountRow"><span id="modalDiscountLabel">Discount</span><span>&minus;&#8369;<span id="modalDiscountAmount">0.00</span></span></div>
            <div class="order-total-row"><span>Total</span><span>&#8369;<span id="modalTotal">0.00</span></span></div>
            <div class="order-row"><span>Cash Tendered</span><span>&#8369;<span id="modalPaid">0.00</span></span></div>
            <div class="order-row"><span>Change</span><span>&#8369;<span id="modalChange">0.00</span></span></div>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn btn-outline btn-block" id="modalCancel">Cancel</button>
            <button type="button" class="btn btn-primary btn-block" id="modalConfirm">Confirm Sale</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// The browser only DISPLAYS prices and totals. The server recomputes everything
// from the database at checkout, so tampering with this page changes nothing.
var DISCOUNT_PERCENT = {{ \App\Models\Sale::DISCOUNT_PERCENT }};
var cart = {};
var grid = document.getElementById('posProductGrid');
var searchInput = document.getElementById('posSearch');
var pills = document.querySelectorAll('#posCategoryPills .pill');
var activeCategory = 'all';

function applyFilters() {
    if (!grid) return;
    var term = searchInput.value.trim().toLowerCase();
    grid.querySelectorAll('.product-card').forEach(function (card) {
        var matchesCategory = activeCategory === 'all' || card.dataset.category === activeCategory;
        var matchesSearch = term === '' || card.dataset.search.indexOf(term) !== -1;
        card.style.display = (matchesCategory && matchesSearch) ? '' : 'none';
    });
}

pills.forEach(function (pill) {
    pill.addEventListener('click', function () {
        pills.forEach(function (p) { p.classList.remove('active'); });
        pill.classList.add('active');
        activeCategory = pill.dataset.category;
        applyFilters();
    });
});
searchInput.addEventListener('input', applyFilters);

if (grid) {
    grid.querySelectorAll('.product-card').forEach(function (card) {
        card.addEventListener('click', function () {
            var id = card.dataset.id;
            var stock = parseInt(card.dataset.stock, 10);
            var existing = cart[id] ? cart[id].qty : 0;
            if (existing + 1 > stock) {
                alert('Not enough stock. Available: ' + stock);
                return;
            }
            cart[id] = { name: card.dataset.name, price: parseFloat(card.dataset.price), qty: existing + 1, stock: stock };
            renderCart();
        });
    });
}

var discountToggle = document.getElementById('discountToggle');
var discountTypeSelect = document.getElementById('discountType');
var discountIdNumber = document.getElementById('discountIdNumber');
var discountConfirmed = document.getElementById('discountConfirmed');
var discountFields = document.getElementById('discountFields');

discountToggle.addEventListener('change', function () {
    var checked = discountToggle.checked;
    discountFields.classList.toggle('open', checked);
    discountTypeSelect.disabled = !checked;
    discountIdNumber.disabled = !checked;
    discountConfirmed.disabled = !checked;
    if (!checked) {
        discountConfirmed.checked = false;
        discountIdNumber.value = '';
    }
    renderCart();
});
discountTypeSelect.addEventListener('change', renderCart);

function changeQty(id, delta) {
    if (!cart[id]) return;
    var next = cart[id].qty + delta;
    if (next < 1) {
        delete cart[id];
    } else if (next > cart[id].stock) {
        alert('Not enough stock. Available: ' + cart[id].stock);
        return;
    } else {
        cart[id].qty = next;
    }
    renderCart();
}

function renderCart() {
    var body = document.getElementById('cartBody');
    var empty = document.getElementById('orderEmpty');
    var hiddenItems = document.getElementById('hiddenItems');
    var ids = Object.keys(cart);
    body.innerHTML = '';
    hiddenItems.innerHTML = '';
    var subtotalCents = 0;

    body.style.display = ids.length === 0 ? 'none' : 'flex';
    empty.style.display = ids.length === 0 ? 'flex' : 'none';

    ids.forEach(function (id, index) {
        var item = cart[id];
        var lineCents = Math.round(item.price * 100) * item.qty;
        subtotalCents += lineCents;

        var row = document.createElement('div');
        row.className = 'order-item';
        row.innerHTML =
            '<div>' +
                '<div class="order-item-name"></div>' +
                '<div class="order-item-meta"></div>' +
            '</div>' +
            '<div class="order-item-actions">' +
                '<button type="button" class="qty-btn" data-action="dec">&minus;</button>' +
                '<button type="button" class="qty-btn" data-action="inc">+</button>' +
                '<button type="button" class="order-item-remove" data-action="remove">&#10005;</button>' +
            '</div>';
        row.querySelector('.order-item-name').textContent = item.name;
        row.querySelector('.order-item-meta').textContent = item.qty + ' × ₱' + item.price.toFixed(2) + ' = ₱' + (lineCents / 100).toFixed(2);
        row.querySelector('[data-action="dec"]').addEventListener('click', function (e) { e.stopPropagation(); changeQty(id, -1); });
        row.querySelector('[data-action="inc"]').addEventListener('click', function (e) { e.stopPropagation(); changeQty(id, 1); });
        row.querySelector('[data-action="remove"]').addEventListener('click', function (e) { e.stopPropagation(); delete cart[id]; renderCart(); });
        body.appendChild(row);

        // Only the product id and quantity are sent; the server looks up the price.
        hiddenItems.innerHTML +=
            '<input type="hidden" name="items[' + index + '][product_id]" value="' + id + '">' +
            '<input type="hidden" name="items[' + index + '][quantity]" value="' + item.qty + '">';
    });

    var discountEnabled = discountToggle.checked;
    var discountCents = discountEnabled ? Math.floor((subtotalCents * DISCOUNT_PERCENT + 50) / 100) : 0;
    var totalCents = subtotalCents - discountCents;

    document.getElementById('cartSubtotal').textContent = (subtotalCents / 100).toFixed(2);
    document.getElementById('discountRow').style.display = discountEnabled ? 'flex' : 'none';
    document.getElementById('discountAmountDisplay').textContent = (discountCents / 100).toFixed(2);
    document.getElementById('cartTotal').textContent = (totalCents / 100).toFixed(2);
    document.getElementById('checkoutBtn').disabled = (ids.length === 0);
    updateChange();
}

function updateChange() {
    var total = parseFloat(document.getElementById('cartTotal').textContent) || 0;
    var paid = parseFloat(document.getElementById('amountPaid').value) || 0;
    var change = paid - total;
    document.getElementById('changeDue').textContent = change >= 0 ? change.toFixed(2) : '0.00';
}

document.getElementById('amountPaid').addEventListener('input', updateChange);

var confirmModal = document.getElementById('confirmModal');

document.getElementById('checkoutBtn').addEventListener('click', function () {
    var total = parseFloat(document.getElementById('cartTotal').textContent) || 0;
    var paid = parseFloat(document.getElementById('amountPaid').value) || 0;

    if (Object.keys(cart).length === 0) {
        return;
    }
    if (discountToggle.checked && discountIdNumber.value.trim() === '') {
        alert('Enter the Senior Citizen / PWD ID number.');
        return;
    }
    if (discountToggle.checked && !discountConfirmed.checked) {
        alert('Please confirm the Senior Citizen / PWD ID was presented.');
        return;
    }
    if (paid < total) {
        alert('Cash tendered is less than the total.');
        return;
    }

    openConfirmModal();
});

function openConfirmModal() {
    var subtotal = parseFloat(document.getElementById('cartSubtotal').textContent) || 0;
    var discountAmount = parseFloat(document.getElementById('discountAmountDisplay').textContent) || 0;
    var total = parseFloat(document.getElementById('cartTotal').textContent) || 0;
    var paid = parseFloat(document.getElementById('amountPaid').value) || 0;
    var discountEnabled = discountToggle.checked;

    var itemsBox = document.getElementById('modalItems');
    itemsBox.innerHTML = '';
    Object.keys(cart).forEach(function (id) {
        var item = cart[id];
        var row = document.createElement('div');
        row.className = 'order-item';
        row.innerHTML = '<div><div class="order-item-name"></div><div class="order-item-meta"></div></div>';
        row.querySelector('.order-item-name').textContent = item.name;
        row.querySelector('.order-item-meta').textContent = item.qty + ' × ₱' + item.price.toFixed(2);
        itemsBox.appendChild(row);
    });

    document.getElementById('modalSubtotal').textContent = subtotal.toFixed(2);
    document.getElementById('modalDiscountRow').style.display = discountEnabled ? 'flex' : 'none';
    document.getElementById('modalDiscountLabel').textContent = discountEnabled
        ? (discountTypeSelect.value === 'pwd' ? 'PWD Discount (-20%)' : 'Senior Citizen Discount (-20%)') + ' — ID ' + discountIdNumber.value.trim()
        : 'Discount';
    document.getElementById('modalDiscountAmount').textContent = discountAmount.toFixed(2);
    document.getElementById('modalTotal').textContent = total.toFixed(2);
    document.getElementById('modalPaid').textContent = paid.toFixed(2);
    document.getElementById('modalChange').textContent = Math.max(paid - total, 0).toFixed(2);

    confirmModal.classList.add('open');
}

document.getElementById('modalCancel').addEventListener('click', function () {
    confirmModal.classList.remove('open');
});

document.getElementById('modalConfirm').addEventListener('click', function () {
    document.getElementById('checkoutForm').submit();
});

renderCart();
</script>
@endpush
