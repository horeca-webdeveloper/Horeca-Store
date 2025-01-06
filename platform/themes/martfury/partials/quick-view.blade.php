<div class="ps-product__header">
    <div class="ps-product__thumbnail" data-vertical="false">
        <div class="ps-product__images" data-arrow="true">
            @foreach ($productImages as $img)
                <div class="item"><img src="{{ RvMedia::getImageUrl($img) }}" alt="{{ $product->name }}"></div>
            @endforeach
        </div>
    </div>
    <div class="ps-product__info">
        <h1><a href="{{ $product->url }}">{!! BaseHelper::clean($product->name) !!}</a></h1>
        <div class="ps-product__meta">
            <p>{{ __('Brand') }}: <a href="{{ $product->brand->url }}">{{ $product->brand->name }}</a></p>
            @if (EcommerceHelper::isReviewEnabled())
                @if ($product->reviews_count > 0)
                    <div class="rating_wrap">
                        <div class="rating">
                            <div class="product_rate" style="width: {{ $product->reviews_avg * 20 }}%"></div>
                        </div>
                        <span class="rating_num">({{ $product->reviews_count }} {{ __('reviews') }})</span>
                    </div>
                @endif
            @endif
        </div>
        <h4 class="ps-product__price @if ($product->front_sale_price !== $product->price) sale @endif"><span>{{ format_price($product->front_sale_price_with_taxes) }}</span> @if ($product->front_sale_price !== $product->price) <del>{{ format_price($product->price_with_taxes) }} </del> @endif</h4>
        <div class="ps-product__desc">
            <div class="ps-list--dot">
                {!! apply_filters('ecommerce_before_product_description', null, $product) !!}
                {!! BaseHelper::clean($product->description) !!}
                {!! apply_filters('ecommerce_after_product_description', null, $product) !!}
            </div>
        </div>
        @if ($product->variations()->count() > 0)
            <div class="pr_switch_wrap">
                {!! render_product_swatches($product, [
                    'selected' => $selectedAttrs,
                    'view'     => Theme::getThemeNamespace() . '::views.ecommerce.attributes.swatches-renderer'
                ]) !!}
            </div>
            <div class="number-items-available" style="display: none; margin-bottom: 10px;"></div>
        @endif

        @if ($product->options()->count() > 0 && isset($product->toArray()['options']))
            <div class="pr_switch_wrap" id="product-option">
                {!! render_product_options($product, $product->toArray()['options']) !!}
            </div>
        @endif

        <form class="add-to-cart-form" method="POST" action="{{ route('public.cart.add-to-cart') }}">
            @csrf
            <div class="ps-product__shopping">
                <input type="hidden" name="id" class="hidden-product-id" value="{{ ($product->is_variation || !$product->defaultVariation->product_id) ? $product->id : $product->defaultVariation->product_id }}"/>
                <input type="hidden" name="qty" value="1">
                @if (EcommerceHelper::isCartEnabled())
                    <button class="ps-btn ps-btn--black" type="submit">{{ __('Add to cart') }}</button>
                    @if (EcommerceHelper::isQuickBuyButtonEnabled())
                        <button class="ps-btn" type="submit" name="checkout">{{ __('Buy Now') }}</button>
                    @endif
                @endif
                <div class="ps-product__actions">
                    @if (EcommerceHelper::isWishlistEnabled())
                        <a class="js-add-to-wishlist-button" href="#" data-url="{{ route('public.wishlist.add', $product->id) }}"><i class="icon-heart"></i></a>
                    @endif
                    @if (EcommerceHelper::isCompareEnabled())
                        <a class="js-add-to-compare-button" href="#" data-url="{{ route('public.compare.add', $product->id) }}"><i class="icon-chart-bars"></i></a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>
