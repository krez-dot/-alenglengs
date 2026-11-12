@extends('layouts.app')
@section('title', 'Edit Product')

@section('content')
<div class="page-centered">
    <div class="page-header">
        <h1>Edit Product</h1>
        <p>Update details and set the reorder level that triggers the Low stock badge.</p>
    </div>
    <div class="card form-card">
        <form method="post" action="{{ route('products.update', $product) }}">
            @csrf @method('PUT')
            @include('products._form')
        </form>
    </div>
</div>
@endsection
