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
    </div>
    <div class="ps-product__container">
        <div class="ps-product__content">
            <a class="ps-product__title" href="{{ $product->url }}">{!! BaseHelper::clean($product->name) !!}</a>
            @if (is_plugin_active('marketplace') && $product->store->id)
                <p class="ps-product__vendor">
                    <span>{{ __('Sold by') }}: </span>
                    <a href="{{ $product->store->url }}" class="text-uppercase">{{ $product->store->name }}</a>
                </p>
            @endif
            @if (EcommerceHelper::isReviewEnabled())
                <div class="rating_wrap">
                    <div class="rating">
                        <div class="product_rate" style="width: {{ $product->reviews_avg * 20 }}%"></div>
                    </div>
                    <span class="rating_num">({{ $product->reviews_count }})</span>
                </div>
            @endif
            <div class="ps-product__desc">
                {!! Str::limit(clean(strip_tags($product->description)), 120) !!}
            </div>
        </div>
        <div class="ps-product__shopping">
            {!! apply_filters('ecommerce_before_product_price_in_listing', null, $product) !!}
            <p class="ps-product__price @if ($product->front_sale_price !== $product->price) sale @endif">{{ format_price($product->front_sale_price_with_taxes) }} @if ($product->front_sale_price !== $product->price) <del>{{ format_price($product->price_with_taxes) }} </del> @endif</p>
            {!! apply_filters('ecommerce_after_product_price_in_listing', null, $product) !!}
            @if (EcommerceHelper::isCartEnabled())
                <a class="ps-btn add-to-cart-button" data-id="{{ $product->id }}" href="#" data-url="{{ route('public.cart.add-to-cart') }}">{{ __('Add to cart') }}</a>
            @endif
            <ul class="ps-product__actions">
                @if (EcommerceHelper::isWishlistEnabled())
                    <li><a class="js-add-to-wishlist-button" href="#" data-url="{{ route('public.wishlist.add', $product->id) }}"><i class="icon-heart"></i> {{ __('Wishlist') }}</a></li>
                @endif
                @if (EcommerceHelper::isCompareEnabled())
                    <li>
                        <a class="js-add-to-compare-button" href="#" data-url="{{ route('public.compare.add', $product->id) }}"><i class="icon-chart-bars"></i>
                        {{ __('Compare') }}</a>
                    </li>
                @endif
            </ul>
        </div>
    </div>
@endif
