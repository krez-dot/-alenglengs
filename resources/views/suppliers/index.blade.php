@extends('layouts.app')
@section('title', 'Suppliers')

@section('content')
@php use App\Support\Ui; @endphp
<div class="page-header page-header-row">
    <div>
        <h1>Suppliers</h1>
        <p>Track who supplies your stock and how to reach them.</p>
    </div>
    <a href="{{ route('suppliers.create') }}" class="btn btn-primary">+ Add Supplier</a>
</div>

<form method="get" class="toolbar">
    <div class="search-input-wrap">
        <span class="search-icon">{{ Ui::navIcon('search') }}</span>
        <input type="text" name="q" class="search-input" placeholder="Search name or contact person..." value="{{ $keyword }}">
    </div>
</form>

<div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Name</th><th>Contact Person</th><th>Phone</th><th>Address</th><th>Actions</th></tr></thead>
        <tbody>
            @forelse ($suppliers as $sup)
                <tr>
                    <td class="strong">{{ $sup->name }}</td>
                    <td>{{ $sup->contact_person ?? '-' }}</td>
                    <td>{{ $sup->phone ?? '-' }}</td>
                    <td>{{ $sup->address ?? '-' }}</td>
                    <td class="actions-cell">
                        <a href="{{ route('suppliers.edit', $sup) }}" class="btn btn-outline btn-sm">Edit</a>
                        <form action="{{ route('suppliers.destroy', $sup) }}" method="post" class="inline-form" onsubmit="return confirm('Delete this supplier?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr class="empty-row"><td colspan="5">No suppliers found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
