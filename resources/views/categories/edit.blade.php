@extends('layouts.app')
@section('title', 'Edit Category')

@section('content')
<div class="page-centered-wide">
    <div class="page-header">
        <h1>Edit Category</h1>
        <p>Rename the category or change its badge color.</p>
    </div>
    @include('categories._form', ['action' => route('categories.update', $category), 'method' => 'PUT'])
</div>
@endsection
