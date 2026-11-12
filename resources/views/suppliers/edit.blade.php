@extends('layouts.app')
@section('title', 'Edit Supplier')

@section('content')
<div class="page-centered">
    <div class="page-header">
        <h1>Edit Supplier</h1>
        <p>Update contact details for this supplier.</p>
    </div>
    <div class="card form-card">
        <form method="post" action="{{ route('suppliers.update', $supplier) }}">
            @csrf @method('PUT')
            @include('suppliers._form')
        </form>
    </div>
</div>
@endsection
