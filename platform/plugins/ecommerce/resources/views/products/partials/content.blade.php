@extends('core/base::layouts.master')

@section('content')
    <div class="container">
        <h2>Edit Product Content</h2>

        <form action="{{ route('ecommerce.product-content.update', $product->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="content">Content</label>
                <textarea name="content" id="content" class="form-control" rows="5">{{ $product->content ?? '' }}</textarea>
            </div>

            <button type="submit" class="btn btn-primary">Update Content</button>
        </form>
    </div>
@endsection
