<div class="ps-compare ps-section--shopping pt-40">
    <div class="container">
        <div class="ps-section__header">
            <h1>{{ __('Compare Product') }}</h1>
        </div>
        <div class="ps-section__content">
            @if ($products->count())
                <div class="table-responsive">
                    <table class="table ps-table--compare">
                        <tbody>
                            <tr>
                                <td class="heading" rowspan="2">{{ __('Product') }}</td>
                                @foreach($products as $product)
                                    <td>
                                        <a class="js-remove-from-compare-button" href="#" data-url="{{ route('public.compare.remove', $product->id) }}">{{ __('Remove') }}</a>
                                    </td>
                                @endforeach
                            </tr>

                            <tr>
                                @foreach($products as $product)
                                    <td>
                                        <div class="ps-product--compare">
                                            <div class="ps-product__thumbnail"><a href="{{ $product->original_product->url }}"><img src="{{ RvMedia::getImageUrl($product->image, 'thumb', false, RvMedia::getDefaultImage()) }}" alt="{{ $product->name }}"></a></div>
                                            <div class="ps-product__content"><a href="{{ $product->original_product->url }}">{!! BaseHelper::clean($product->name) !!}</a></div>
                                        </div>
                                    </td>
                                @endforeach
                            </tr>

                            @if (EcommerceHelper::isReviewEnabled())
                                <tr>
                                    <td class="heading">{{ __('Rating') }}</td>
                                    @foreach($products as $product)
                                        <td>
                                            <div class="rating_wrap">
                                                <div class="rating">
                                                    <div class="product_rate" style="width: {{ $product->reviews_avg * 20 }}%"></div>
                                                </div>
                                                <span class="rating_num">({{ $product->reviews_count }})</span>
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>
                            @endif

                            <tr>
                                <td class="heading">{{ __('Price') }}</td>
                                @foreach($products as $product)
                                    <td>
                                        <h4 class="price @if ($product->front_sale_price !== $product->price) sale @endif"><span>{{ format_price($product->front_sale_price_with_taxes) }}</span> @if ($product->front_sale_price !== $product->price) &nbsp;<del>{{ format_price($product->price_with_taxes) }} </del> <small>({{ get_sale_percentage($product->price, $product->front_sale_price) }})</small> @endif</h4>
                                    </td>
                                @endforeach
                            </tr>

                            <tr>
                                <td class="heading">{{ __('Description') }}</td>
                                @foreach($products as $product)
                                    <td>
                                        {!! BaseHelper::clean($product->description) !!}
                                    </td>
                                @endforeach
                            </tr>

                            @foreach($attributeSets as $attributeSet)
                                @if ($attributeSet->is_comparable)
                                    <tr>
                                        <td class="heading">
                                            {{ $attributeSet->title }}
                                        </td>

                                        @foreach($products as $product)
                                            @php
                                                $attributes = app(\Botble\Ecommerce\Repositories\Interfaces\ProductInterface::class)->getRelatedProductAttributes($product)->where('attribute_set_id', $attributeSet->id)->sortBy('order');
                                            @endphp

                                            @if ($attributes->count())
                                                @if ($attributeSet->display_layout == 'dropdown')
                                                    <td>
                                                        {{ $attributes->pluck('title')->implode(', ') }}
                                                    </td>
                                                @elseif ($attributeSet->display_layout == 'text')
                                                    <td>
                                                        <div class="attribute-values">
                                                            <ul class="text-swatch attribute-swatch color-swatch">
                                                                @foreach($attributes as $attribute)
                                                                    <li class="attribute-swatch-item" style="display: inline-block">
                                                                        <label>
                                                                            <input class="form-control product-filter-item" type="radio" disabled>
                                                                            <span style="cursor: default">{{ $attribute->title }}</span>
                                                                        </label>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    </td>
                                                @else
                                                    <td>
                                                        <div class="attribute-values">
                                                            <ul class="visual-swatch color-swatch attribute-swatch">
                                                            @foreach($attributes as $attribute)
                                                                <li class="attribute-swatch-item" style="display: inline-block">
                                                                    <div class="custom-radio">
                                                                        <label>
                                                                            <input class="form-control product-filter-item" type="radio" disabled>
                                                                            <span style="{{ $attribute->image ? 'background-image: url(' . RvMedia::getImageUrl($attribute->image) . ');' : 'background-color: ' . $attribute->color . ';' }}; cursor: default;"></span>
                                                                        </label>
                                                                    </div>
                                                                </li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    </td>
                                                @endif
                                            @else
                                                <td>&mdash;</td>
                                            @endif
                                        @endforeach
                                    </tr>
                                @endif
                            @endforeach

                            @if (EcommerceHelper::isCartEnabled())
                                <tr>
                                    <td class="heading"></td>
                                    @foreach($products as $product)
                                        <td>
                                            <div class="ps-product p-0 border-0">
                                                <div class="ps-product__actions mw-100 justify-content-end">
                                                    <a class="ps-btn add-to-cart-button" data-id="{{ $product->id }}" href="#" data-url="{{ route('public.cart.add-to-cart') }}">{{ __('Add To Cart') }}</a>
                                                </div>
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-center">{{ __('No products in compare list!') }}</p>
            @endif
        </div>
    </div>
</div>
