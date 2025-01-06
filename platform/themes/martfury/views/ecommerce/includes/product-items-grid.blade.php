<div class="loading">
    <div class="half-circle-spinner">
        <div class="circle circle-1"></div>
        <div class="circle circle-2"></div>
    </div>
</div>

<input type="hidden" name="page" data-value="{{ $products->currentPage() }}">
<input type="hidden" name="q" value="{{ BaseHelper::stringify(request()->query('q')) }}">

<div class="ps-shopping-product">
    @if ($products->isNotEmpty())
        <div class="row">
            @foreach($products as $product)
                <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 col-6">
                    <div class="ps-product">
                        {!! Theme::partial('product-item', compact('product')) !!}
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="alert alert-warning mt-4 w-100" role="alert">
            {{ __(':total Product found', ['total' => 0]) }}
        </div>
    @endif
</div>
<div class="ps-pagination">
    {!! $products->withQueryString()->links() !!}
</div>
