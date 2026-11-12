@extends('layouts.app')
@section('title', 'Add Product')

@section('content')
<div class="page-centered">
    <div class="page-header">
        <h1>Add New Product</h1>
        <p>Product details are visible to cashiers on the checkout screen.</p>
    </div>
    <div class="card form-card">
        <form method="post" action="{{ route('products.store') }}">
            @csrf
            @include('products._form')
        </form>
    </div>
</div>
@endsection
