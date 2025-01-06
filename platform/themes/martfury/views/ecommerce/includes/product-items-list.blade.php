<div class="loading">
    <div class="half-circle-spinner">
        <div class="circle circle-1"></div>
        <div class="circle circle-2"></div>
    </div>
</div>

<input type="hidden" name="page" data-value="{{ $products->currentPage() }}">
<input type="hidden" name="q" value="{{ BaseHelper::stringify(request()->query('q')) }}">

<div class="ps-shopping-product">
    @forelse ($products as $product)
        <div class="ps-product ps-product--width">
            {!! Theme::partial('product-item-grid', compact('product')) !!}
        </div>
    @empty
        <div class="alert alert-warning mt-4 w-100" role="alert">
            {{ __(':total Product found', ['total' => 0]) }}
        </div>
    @endforelse
</div>
<div class="ps-pagination">
    {!! $products->withQueryString()->links() !!}
</div>
