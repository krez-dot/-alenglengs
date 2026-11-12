@extends('layouts.app')
@section('title', 'Categories')

@section('content')
@php use App\Support\Ui; @endphp
<div class="page-header page-header-row">
    <div>
        <h1>Categories</h1>
        <p>Group products so cashiers can filter the checkout screen quickly.</p>
    </div>
    <a href="{{ route('categories.create') }}" class="btn btn-primary">+ Add Category</a>
</div>

<div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Name</th><th>Created</th><th>Actions</th></tr></thead>
        <tbody>
            @forelse ($categories as $cat)
                <tr>
                    <td><span class="badge-pill {{ Ui::categoryBadgeClass($cat->name, $cat->color) }}">{{ $cat->name }}</span></td>
                    <td class="muted">{{ $cat->created_at }}</td>
                    <td class="actions-cell">
                        <a href="{{ route('categories.edit', $cat) }}" class="btn btn-outline btn-sm">Edit</a>
                        <form action="{{ route('categories.destroy', $cat) }}" method="post" class="inline-form" onsubmit="return confirm('Delete this category?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr class="empty-row"><td colspan="3">No categories found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
