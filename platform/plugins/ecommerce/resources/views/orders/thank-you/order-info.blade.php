<div class="pt-3 mb-5">
    <div class="align-items-center">
        <h6 class="d-inline-block">{{ __('Order number') }}: {{ $order->code }}</h6>
    </div>

    <div class="checkout-success-products">
        <div id="{{ 'cart-item-' . $order->id }}">
            @foreach ($order->products as $orderProduct)
                <div class="row cart-item">
                    <div class="col-lg-3 col-md-3">
                        <div class="checkout-product-img-wrapper d-inline-block">
                            <img
                                class="item-thumb img-thumbnail img-rounded mb-2 mb-md-0"
                                src="{{ RvMedia::getImageUrl($orderProduct->product_image, 'thumb', false, RvMedia::getDefaultImage()) }}"
                                alt="{{ $orderProduct->product_name }}"
                            >
                            <span class="checkout-quantity">{{ $orderProduct->qty }}</span>
                        </div>
                    </div>
                    <div class="col-lg-5 col-md-5">
                        <p class="mb-2 mb-md-0">{!! BaseHelper::clean($orderProduct->product_name) !!}</p>
                        <p class="mb-2 mb-md-0">
                            <small>{{ Arr::get($orderProduct->options, 'attributes', '') }}</small>
                        </p>
                        @if (!empty($orderProduct->product_options) && is_array($orderProduct->product_options))
                            {!! render_product_options_html($orderProduct->product_options, $orderProduct->price) !!}
                        @endif

                        @include(EcommerceHelper::viewPath('includes.cart-item-options-extras'), [
                            'options' => $orderProduct->options,
                        ])
                    </div>
                    <div class="col-lg-4 col-md-4 col-4 float-md-end text-md-end">
                        <p>{{ format_price($orderProduct->price) }}</p>
                    </div>
                </div>
            @endforeach

            @if (!empty($isShowTotalInfo))
                @include('plugins/ecommerce::orders.thank-you.total-info', compact('order'))
            @endif
        </div>
    </div>
</div>
