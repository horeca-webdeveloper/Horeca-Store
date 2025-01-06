<div class="ps-section--shopping ps-shopping-cart pt-40">
    <div class="container">
        <div class="ps-section__header">
            <h1>{{ __('Shopping Cart') }}</h1>
        </div>
        <div class="ps-section__content">
            <form class="form--shopping-cart" method="post" action="{{ route('public.cart.update') }}">
                @csrf
                    @if (count($products) > 0)
                            <div class="table-responsive">
                                <table class="table ps-table--shopping-cart">
                                    <thead>
                                    <tr>
                                        <th>{{ __("Product's name") }}</th>
                                        <th>{{ __('Price') }}</th>
                                        <th>{{ __('Quantity') }}</th>
                                        <th>{{ __('Total') }}</th>
                                        <th></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                        @foreach(Cart::instance('cart')->content() as $key => $cartItem)
                                            @php
                                                $product = $products->find($cartItem->id);
                                            @endphp

                                            @if (!empty($product))
                                                <tr>
                                                    <td>
                                                        <input type="hidden" name="items[{{ $key }}][rowId]" value="{{ $cartItem->rowId }}">
                                                        <div class="ps-product--cart">
                                                            <div class="ps-product__thumbnail">
                                                                <a href="{{ $product->original_product->url }}">
                                                                    <img src="{{ RvMedia::getImageUrl($cartItem->options['image'], 'thumb', false, RvMedia::getDefaultImage()) }}" alt="{{ $product->original_product->name }}" />
                                                                </a>
                                                            </div>
                                                            <div class="ps-product__content">
                                                                <a href="{{ $product->original_product->url }}">{{ $product->original_product->name }}  @if ($product->isOutOfStock()) <span class="stock-status-label">({!! $product->stock_status_html !!})</span> @endif</a>
                                                                @if (is_plugin_active('marketplace') && $product->original_product->store->id)
                                                                    <p class="d-block mb-0 sold-by"><small>{{ __('Sold by') }}: <a
                                                                                href="{{ $product->original_product->store->url }}">{{ $product->original_product->store->name }}</a></small></p>
                                                                @endif

                                                                <p class="mb-0"><small>{{ $cartItem->options['attributes'] ?? '' }}</small></p>

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
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="price text-center">
                                                        <div class="product__price @if ($product->front_sale_price != $product->price) sale @endif">
                                                            <span>{{ format_price($cartItem->price) }}</span>
                                                            @if ($product->front_sale_price != $product->price)
                                                                <small><del>{{ format_price($product->price) }}</del></small>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="form-group--number product__qty">
                                                            <button class="up">+</button>
                                                            <button class="down">-</button>
                                                            <input type="number" class="form-control qty-input" min="1" value="{{ $cartItem->qty }}" title="{{ __('Qty') }}" name="items[{{ $key }}][values][qty]">
                                                        </div>
                                                    </td>
                                                    <td class="text-center">{{ format_price($cartItem->price * $cartItem->qty) }}</td>
                                                    <td><a href="#" data-url="{{ route('public.cart.remove', $cartItem->rowId) }}" class="remove-cart-button"><i class="icon-cross"></i></a></td>
                                                </tr>
                                                @endif
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                    @else
                        <p class="text-center">{{ __('Your cart is empty!') }}</p>
                    @endif
                </form>
        </div>
        @if (count($products) > 0)
            <div class="ps-section__footer">
                <div class="row">
                    <div class="col-lg-6 col-md-12 form-coupon-wrapper">
                        <figure>
                            <figcaption>{{ __('Coupon Discount') }}</figcaption>
                            <div class="form-group">
                                <input class="form-control coupon-code" type="text" name="coupon_code" value="{{ old('coupon_code') }}" placeholder="{{ __('Enter coupon code') }}">
                            </div>
                            <div class="form-group">
                                <button class="ps-btn ps-btn--outline btn-apply-coupon-code" type="button" data-url="{{ route('public.coupon.apply') }}">{{ __('Apply') }}</button>
                            </div>
                        </figure>
                    </div>
                    <div class="col-lg-6 col-md-12 col-sm-12 ">
                        <div class="ps-block--shopping-total">
                            <div class="ps-block__header">
                                <p>{{ __('Subtotal') }} <span> {{ format_price(Cart::instance('cart')->rawSubTotal()) }}</span></p>
                            </div>
                            @if (EcommerceHelper::isTaxEnabled())
                                <div class="ps-block__header">
                                    <p>{{ __('Tax') }} <span> {{ format_price(Cart::instance('cart')->rawTax()) }}</span></p>
                                </div>
                            @endif
                            @if ($couponDiscountAmount > 0 && session('applied_coupon_code'))
                                <div class="ps-block__header">
                                    <p>{{ __('Coupon code: :code', ['code' => session('applied_coupon_code')]) }} (<small><a class="btn-remove-coupon-code text-danger" data-url="{{ route('public.coupon.remove') }}" href="javascript:void(0)" data-processing-text="{{ __('Removing...') }}">{{ __('Remove') }}</a></small>)<span> {{ format_price($couponDiscountAmount) }}</span></p>
                                </div>
                            @endif
                            @if ($promotionDiscountAmount)
                                <div class="ps-block__header">
                                    <p>{{ __('Discount promotion') }} <span> {{ format_price($promotionDiscountAmount) }}</span></p>
                                </div>
                            @endif
                            <div class="ps-block__content">
                                <h3>{{ __('Total') }} <span>{{ ($promotionDiscountAmount + $couponDiscountAmount) > Cart::instance('cart')->rawTotal() ? format_price(0) : format_price(Cart::instance('cart')->rawTotal() - $promotionDiscountAmount - $couponDiscountAmount) }}</span></h3>
                                <p><small>({{ __('Shipping fees not included') }})</small></p>
                            </div>
                        </div>
                        <a class="ps-btn btn-cart-button-action" href="{{ route('public.products') }}"><i class="icon-arrow-left"></i> {{ __('Back to Shop') }}</a>
                        <a class="ps-btn ps-btn btn-cart-button-action" href="{{ route('public.checkout.information', OrderHelper::getOrderSessionToken()) }}">{{ __('Proceed to checkout') }} <i class="icon-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        @endif

        {!! Theme::partial('cross-sell-products', compact('crossSellProducts')) !!}
    </div>
</div>
