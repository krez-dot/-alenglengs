<div class="field">
    <label>Supplier Name</label>
    <input type="text" name="name" value="{{ old('name', $supplier->name) }}" required>
</div>
<div class="field">
    <label>Contact Person</label>
    <input type="text" name="contact_person" value="{{ old('contact_person', $supplier->contact_person) }}">
</div>
<div class="field">
    <label>Phone</label>
    <input type="text" name="phone" value="{{ old('phone', $supplier->phone) }}">
</div>
<div class="field">
    <label>Address</label>
    <input type="text" name="address" value="{{ old('address', $supplier->address) }}">
</div>
<div class="form-actions">
    <button type="submit" class="btn btn-primary">Save</button>
    <a href="{{ route('suppliers.index') }}" class="btn btn-outline">Cancel</a>
</div>
