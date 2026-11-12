@extends('layouts.app')
@section('title', 'Add Category')

@section('content')
<div class="page-centered-wide">
    <div class="page-header">
        <h1>Add Category</h1>
        <p>Categories help organize the product catalog and checkout filters.</p>
    </div>
    @include('categories._form', ['action' => route('categories.store')])
</div>
@endsection
