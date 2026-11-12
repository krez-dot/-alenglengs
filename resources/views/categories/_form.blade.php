@php use App\Support\Ui; @endphp
<div class="form-with-preview">
    <div class="card form-card">
        <form method="post" action="{{ $action }}" id="categoryForm">
            @csrf
            @isset($method) @method($method) @endisset
            <div class="field">
                <label>Category Name</label>
                <input type="text" name="name" id="categoryNameInput" value="{{ old('name', $category->name) }}" required>
            </div>
            <div class="field">
                <label>Badge Color</label>
                {{ Ui::colorSwatchPicker((string) old('color', $category->color)) }}
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('categories.index') }}" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
    <div class="card preview-card">
        <div class="card-header-row"><strong>Preview</strong></div>
        <div class="preview-body">
            <p class="empty-text">This is how it'll look on Products and Checkout.</p>
            <span class="badge-pill badge-default" id="colorPreviewBadge">Category Name</span>
            <p class="empty-text" id="colorPreviewNote">Auto-assigned once saved.</p>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var nameInput = document.getElementById('categoryNameInput');
    var colorRadios = document.querySelectorAll('input[name="color"]');
    var badge = document.getElementById('colorPreviewBadge');
    var note = document.getElementById('colorPreviewNote');
    function update() {
        badge.textContent = nameInput.value.trim() || 'Category Name';
        var checked = document.querySelector('input[name="color"]:checked');
        var color = checked ? checked.value : '';
        badge.className = 'badge-pill ' + (color ? 'badge-' + color : 'badge-default');
        note.style.display = color ? 'none' : 'block';
    }
    nameInput.addEventListener('input', update);
    colorRadios.forEach(function (radio) { radio.addEventListener('change', update); });
    update();
})();
</script>
@endpush
