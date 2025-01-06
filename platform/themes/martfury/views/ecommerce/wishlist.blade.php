<div class="ps-section--shopping pt-40">
    <div class="container">
        <div class="ps-section__header">
            <h1>{{ __('Wishlist') }}</h1>
        </div>
        <div class="ps-section__content">
            @if ($products->total())
                <div class="table-responsive">
                    <table class="table ps-table--wishlist ps-table--responsive">
                        <thead>
                        <tr>
                            <th></th>
                            <th class="text-left">{{ __('Image') }}</th>
                            <th class="text-left">{{ __('Price') }}</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                            @foreach ($products as $product)
                                <tr>
                                    <td data-label="{{ __('Remove') }}">&nbsp;<a class="js-remove-from-wishlist-button" href="#" data-url="{{ route('public.wishlist.remove', $product->id) }}"><i class="icon-cross"></i></a></td>
                                    <td data-label="{{ __('Product') }}">
                                        <div class="ps-product--cart">
                                            <div class="ps-product__thumbnail"><a href="{{ $product->original_product->url }}"><img src="{{ RvMedia::getImageUrl($product->image, 'thumb', false, RvMedia::getDefaultImage()) }}" alt="{{ $product->name }}"></a></div>
                                            <div class="ps-product__content">
                                                <a href="{{ $product->original_product->url }}">{!! BaseHelper::clean($product->name) !!}</a>
                                                @if (EcommerceHelper::isReviewEnabled())
                                                    @php $countRating = $product->reviews()->count(); @endphp
                                                    @if ($countRating > 0)
                                                        <div class="rating_wrap">
                                                            <div class="rating">
                                                                <div class="product_rate" style="width: {{ $product->reviews()->avg('star') * 20 }}%"></div>
                                                            </div>
                                                            <span class="rating_num">({{ $countRating }})</span>
                                                        </div>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="price" data-label="{{ __('Price') }}"><span>{{ format_price($product->front_sale_price_with_taxes) }}</span> @if ($product->front_sale_price !== $product->price) &nbsp;<del>{{ format_price($product->price_with_taxes) }} </del> @endif</td>
                                    @if (EcommerceHelper::isCartEnabled())
                                        <td data-label="{{ __('Action') }}">
                                            <div class="ps-product p-0 border-0">
                                                <div class="ps-product__actions mw-100 justify-content-end">
                                                    <a class="ps-btn add-to-cart-button" data-id="{{ $product->id }}" href="#" data-url="{{ route('public.cart.add-to-cart') }}">{{ __('Add to cart') }}</a>
                                                </div>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="ps-pagination">
                        {!! $products->links() !!}
                    </div>
                </div>
            @else
                <p class="text-center">{{ __('No product in wishlist!') }}</p>
            @endif
        </div>
    </div>
</div>
