@if ($products->count())
    <div class="ps-panel__content">
        @foreach($products as $product)
            <div class="ps-product ps-product--width ps-product--search-result">
                <div class="ps-product__thumbnail">
                    <a href="{{ $product->url }}">
                        <img src="{{ RvMedia::getImageUrl($product->image, 'small', false, RvMedia::getDefaultImage()) }}" alt="{{ $product->name }}">
                    </a>
                </div>
                    <div class="ps-product__content">
                    <a class="ps-product__title" href="{{ $product->url }}">{!! BaseHelper::clean($product->name) !!}</a>
                    @if (EcommerceHelper::isReviewEnabled())
                        @if ($product->reviews_avg > 0)
                            <div class="rating_wrap">
                                <div class="rating">
                                    <div class="product_rate" style="width: {{ $product->reviews_avg * 20 }}%"></div>
                                </div>
                                <span class="rating_num">({{ $product->reviews_count }})</span>
                            </div>
                        @endif
                    @endif
                    <p class="ps-product__price @if ($product->front_sale_price !== $product->price) sale @endif">{{ format_price($product->front_sale_price_with_taxes) }} @if ($product->front_sale_price !== $product->price) <del>{{ format_price($product->price_with_taxes) }} </del> @endif</p>
                </div>
            </div>
        @endforeach
    </div>
    <div class="ps-panel__footer text-center"><a href="{{ route('public.products') }}?q={{ $query }}">{{ __('See all results') }}</a></div>
@else
    <div class="text-center">{{ __('No products found.') }}</a></div>
@endif
