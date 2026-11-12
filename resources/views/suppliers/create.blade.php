@extends('layouts.app')
@section('title', 'Add Supplier')

@section('content')
<div class="page-centered">
    <div class="page-header">
        <h1>Add Supplier</h1>
        <p>Keep supplier contact details handy for reordering stock.</p>
    </div>
    <div class="card form-card">
        <form method="post" action="{{ route('suppliers.store') }}">
            @csrf
            @include('suppliers._form')
        </form>
    </div>
</div>
@endsection
