@if ($product)
    <div class="ps-product__thumbnail">
        <a href="{{ $product->url }}">
            <img src="{{ RvMedia::getImageUrl($product->image, 'small', false, RvMedia::getDefaultImage()) }}" alt="{{ $product->name }}">
        </a>
        @if ($product->isOutOfStock())
            <div class="ps-product__badges">
                <span class="ps-product__badge out-stock">{{ __('Out Of Stock') }}</span>
            </div>
        @else
            @if ($product->productLabels->count())
                <div class="ps-product__badges">
                    @foreach ($product->productLabels as $label)
                        <span class="ps-product__badge" @if ($label->color) style="background-color: {{ $label->color }} !important;" @endif>{{ $label->name }}</span>
                    @endforeach
                </div>
            @else
                @if ($product->front_sale_price !== $product->price)
                    <div class="ps-product__badges">
                        <div class="ps-product__badge">{{ get_sale_percentage($product->price, $product->front_sale_price) }}</div>
                    </div>
                @endif
            @endif
        @endif
        <ul class="ps-product__actions">
            @if (EcommerceHelper::isCartEnabled())
                <li><a class="add-to-cart-button" data-id="{{ $product->id }}" href="#" data-url="{{ route('public.cart.add-to-cart') }}" title="{{ __('Add To Cart') }}"><i class="icon-bag2"></i></a></li>
            @endif
            <li><a class="js-quick-view-button" href="#" data-url="{{ route('public.ajax.quick-view', $product->id) }}" title="{{ __('Quick View') }}"><i class="icon-eye"></i></a></li>
            @if (EcommerceHelper::isWishlistEnabled())
                <li><a class="js-add-to-wishlist-button" href="#" data-url="{{ route('public.wishlist.add', $product->id) }}" title="{{ __('Add to Wishlist') }}"><i class="icon-heart"></i></a></li>
            @endif
            @if (EcommerceHelper::isCompareEnabled())
                <li><a class="js-add-to-compare-button" href="#" data-url="{{ route('public.compare.add', $product->id) }}" title="{{ __('Compare') }}"><i class="icon-chart-bars"></i></a></li>
            @endif
        </ul>
    </div>
    <div class="ps-product__container">
        @if (is_plugin_active('marketplace') && $product->store->id)
            <a class="ps-product__vendor" href="{{ $product->store->url }}">{{ $product->store->name }}</a>
        @endif
        <div class="ps-product__content">
            <a class="ps-product__title" href="{{ $product->url }}">{!! BaseHelper::clean($product->name) !!}</a>
            @if (EcommerceHelper::isReviewEnabled())
                <div class="rating_wrap">
                    <div class="rating">
                        <div class="product_rate" style="width: {{ $product->reviews_avg * 20 }}%"></div>
                    </div>
                    <span class="rating_num">({{ $product->reviews_count }})</span>
                </div>
            @endif
            {!! apply_filters('ecommerce_before_product_price_in_listing', null, $product) !!}
            <p class="ps-product__price @if ($product->front_sale_price !== $product->price) sale @endif">{{ format_price($product->front_sale_price_with_taxes) }} @if ($product->front_sale_price !== $product->price) <del>{{ format_price($product->price_with_taxes) }} </del> @endif</p>
            {!! apply_filters('ecommerce_after_product_price_in_listing', null, $product) !!}
        </div>
    </div>
@endif
