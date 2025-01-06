<div class="ps-cart__content">
    @if (Cart::instance('cart')->count() > 0 && Cart::instance('cart')->products()->count() > 0)
        <div class="ps-cart__items">
            <div class="ps-cart__items__body">
                @php
                    $products = Cart::instance('cart')->products();
                @endphp
                @if (count($products))
                    @foreach(Cart::instance('cart')->content() as $key => $cartItem)
                        @php
                            $product = $products->find($cartItem->id);
                        @endphp

                        @if (!empty($product))
                            <div class="ps-product--cart-mobile">
                                <div class="ps-product__thumbnail">
                                    <a href="{{ $product->original_product->url }}"><img src="{{ RvMedia::getImageUrl($cartItem->options->image, 'thumb', false, RvMedia::getDefaultImage()) }}" alt="{{ $product->original_product->name }}" /></a>
                                </div>
                                <div class="ps-product__content">
                                    <a class="ps-product__remove remove-cart-item" href="#" data-url="{{ route('public.cart.remove', $cartItem->rowId) }}"><i class="icon-cross"></i></a>
                                    <a href="{{ $product->original_product->url }}"> {{ $product->original_product->name }}  @if ($product->isOutOfStock()) <span class="stock-status-label">({!! $product->stock_status_html !!})</span> @endif</a>
                                    <p class="mb-0">
                                        <small>
                                            <span class="d-inline-block">{{ $cartItem->qty }} x</span> <span class="cart-price">{{ format_price($cartItem->price) }} @if ($product->front_sale_price != $product->price)
                                                    <small><del>{{ format_price($product->price) }}</del></small>
                                                @endif
                                            </span>
                                        </small>
                                    </p>
                                    <p class="mb-0"><small><small>{{ $cartItem->options['attributes'] ?? '' }}</small></small></p>

                                    @if (!empty($cartItem->options['options']))
                                        {!! render_product_options_info($cartItem->options['options'], $product, true) !!}
                                    @endif

                                    @if (!empty($cartItem->options['extras']) && is_array($cartItem->options['extras']))
                                        @foreach($cartItem->options['extras'] as $option)
                                            @if (!empty($option['key']) && !empty($option['value']))
                                                <p class="mb-0"><small>{{ $option['key'] }}: <strong> {{ $option['value'] }}</strong></small></p>
                                            @endif
                                        @endforeach
                                    @endif
                                    @if (is_plugin_active('marketplace') && $product->original_product->store->id)
                                        <p class="d-block mb-0 sold-by">
                                            <small>{{ __('Sold by') }}: <a href="{{ $product->original_product->store->url }}">{{ $product->original_product->store->name }}</a>
                                            </small>
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach
                @endif
            </div>
        </div>
        <div class="ps-cart__footer">
            @if (EcommerceHelper::isTaxEnabled())
                <h5>{{ __('Sub Total') }}:<strong>{{ format_price(Cart::instance('cart')->rawSubTotal()) }}</strong></h5>
                <h5>{{ __('Tax') }}:<strong>{{ format_price(Cart::instance('cart')->rawTax()) }}</strong></h5>
                <h3>{{ __('Total') }}:<strong>{{ format_price(Cart::instance('cart')->rawSubTotal() + Cart::instance('cart')->rawTax()) }}</strong></h3>
            @else
                <h3>{{ __('Sub Total') }}:<strong>{{ format_price(Cart::instance('cart')->rawSubTotal()) }}</strong></h3>
            @endif
            <figure>
                <a class="ps-btn" href="{{ route('public.cart') }}">{{ __('View Cart') }}</a>
                @if (session('tracked_start_checkout'))
                    <a href="{{ route('public.checkout.information', session('tracked_start_checkout')) }}" class="ps-btn">{{ __('Checkout') }}</a>
                @endif
            </figure>
        </div>
    @else
        <div class="ps-cart__items ps-cart_no_items">
            <span class="cart-empty-message">{{ __('No products in the cart.') }}</span>
        </div>
    @endif
</div>
