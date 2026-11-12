<div class="field">
    <label>SKU</label>
    <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" required>
</div>
<div class="field">
    <label>Product Name</label>
    <input type="text" name="name" value="{{ old('name', $product->name) }}" required>
</div>
<div class="field-row">
    <div class="field">
        <label>Category</label>
        <select name="category_id" required>
            <option value="">-- Select Category --</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->id }}" @selected((string) old('category_id', $product->category_id) === (string) $cat->id)>{{ $cat->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label>Supplier (optional)</label>
        <select name="supplier_id">
            <option value="">-- None --</option>
            @foreach ($suppliers as $sup)
                <option value="{{ $sup->id }}" @selected((string) old('supplier_id', $product->supplier_id) === (string) $sup->id)>{{ $sup->name }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="field-row">
    <div class="field">
        <label>Cost Price (&#8369;)</label>
        <input type="number" step="0.01" min="0" name="cost_price" value="{{ old('cost_price', $product->cost_price) }}" required>
    </div>
    <div class="field">
        <label>Unit Price (&#8369;)</label>
        <input type="number" step="0.01" min="0" name="selling_price" value="{{ old('selling_price', $product->selling_price) }}" required>
    </div>
</div>
<div class="field-row">
    <div class="field">
        <label>Stock Quantity</label>
        <input type="number" min="0" name="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity) }}" required>
    </div>
    <div class="field">
        <label>Reorder Level</label>
        <input type="number" min="0" name="reorder_level" value="{{ old('reorder_level', $product->reorder_level) }}" required>
    </div>
</div>
<div class="form-actions">
    <button type="submit" class="btn btn-primary">Save Product</button>
    <a href="{{ route('products.index') }}" class="btn btn-outline">Cancel</a>
</div>
