@php
    Theme::set('stickyHeader', 'false');
    Theme::set('topHeader', Theme::partial('header-product-page', compact('product')));
    Theme::set('bottomFooter', Theme::partial('footer-product-page', compact('product')));
    Theme::set('pageId', 'product-page');
    Theme::set('headerMobile', Theme::partial('header-mobile-product'));
@endphp

<div class="ps-page--product">
    <div class="ps-container" id="app">
            <div class="ps-page__container">
                <div class="ps-page__left">
                    <div class="ps-product--detail ps-product--fullwidth">
                        <div class="ps-product__header">
                            <div class="ps-product__thumbnail" data-vertical="true">
                                <figure>
                                    <div class="ps-wrapper">
                                        <div class="ps-product__gallery" data-arrow="false">
                                            @foreach ($productImages as $img)
                                                <div class="item">
                                                    <a href="{{ RvMedia::getImageUrl($img) }}">
                                                        <img src="{{ RvMedia::getImageUrl($img) }}" alt="{{ $product->name }}"/>
                                                    </a>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </figure>
                                <div class="ps-product__variants" data-item="4" data-md="4" data-sm="4" data-arrow="true">
                                    @foreach ($productImages as $img)
                                        <div class="item">
                                            <img src="{{ RvMedia::getImageUrl($img, 'thumb') }}" alt="{{ $product->name }}"/>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="ps-product__info">
                                <h1>{{ $product->name }}</h1>
                                <div class="ps-product__meta">
                                    @if ($product->brand_id)
                                        <p>{{ __('Brand') }}: <a href="{{ $product->brand->url }}">{{ $product->brand->name }}</a></p>
                                    @endif

                                    @if (EcommerceHelper::isReviewEnabled() && $product->reviews_count > 0)
                                        <div class="rating_wrap">
                                            <a href="#tab-reviews">
                                                <div class="rating">
                                                    <div class="product_rate" style="width: {{ $product->reviews_avg * 20 }}%"></div>
                                                </div>
                                                <span class="rating_num">({{ $product->reviews_count }} {{ __('reviews') }})</span>
                                            </a>
                                        </div>
                                    @endif
                                </div>
                                <h4 class="ps-product__price @if ($product->front_sale_price !== $product->price) sale @endif"><span>{{ format_price($product->front_sale_price_with_taxes) }}</span> @if ($product->front_sale_price !== $product->price) &nbsp;<del>{{ format_price($product->price_with_taxes) }} </del> @endif</h4>
                                <div class="ps-product__desc">
                                    @if (is_plugin_active('marketplace') && $product->store_id)
                                        <p>{{ __('Sold By') }}: <a href="{{ $product->store->url }}"><strong>{{ $product->store->name }}</strong></a></p>
                                    @endif

                                    <div class="ps-list--dot">
                                        {!! apply_filters('ecommerce_before_product_description', null, $product) !!}
                                        {!! BaseHelper::clean($product->description) !!}
                                        {!! apply_filters('ecommerce_after_product_description', null, $product) !!}
                                    </div>
                                </div>
                                @php $flashSale = $product->latestFlashSales()->first(); @endphp

                                @if ($flashSale)
                                    <div class="ps-product__countdown">
                                        <figure>
                                            <figcaption> {{ __("Don't Miss Out! This promotion will expires in") }}</figcaption>
                                            <ul class="ps-countdown" data-time="{{ $flashSale->end_date }}">
                                                <li><span class="days"></span>
                                                    <p>{{ __('Days') }}</p>
                                                </li>
                                                <li><span class="hours"></span>
                                                    <p>{{ __('Hours') }}</p>
                                                </li>
                                                <li><span class="minutes"></span>
                                                    <p>{{ __('Minutes') }}</p>
                                                </li>
                                                <li><span class="seconds"></span>
                                                    <p>{{ __('Seconds') }}</p>
                                                </li>
                                            </ul>
                                        </figure>
                                        <figure>
                                            <figcaption>{{ __('Sold Items') }}</figcaption>
                                            <div class="ps-product__progress-bar ps-progress" data-value="{{ $flashSale->pivot->quantity > 0 ? ($flashSale->pivot->sold / $flashSale->pivot->quantity) * 100 : 0 }}">
                                                <div class="ps-progress__value"><span style="width: {{ $flashSale->pivot->quantity > 0 ? $flashSale->pivot->sold / $flashSale->pivot->quantity : 0 }}%;"></span></div>
                                                <p><b>{{ $flashSale->pivot->sold }}/{{ $flashSale->pivot->quantity }}</b> {{ __('Sold') }}</p>
                                            </div>
                                        </figure>
                                    </div>
                                @endif

                                <form class="add-to-cart-form" method="POST" action="{{ route('public.cart.add-to-cart') }}">
                                    @csrf
                                    @if ($product->variations()->count() > 0)
                                        <div class="pr_switch_wrap">
                                            {!! render_product_swatches($product, [
                                                'selected' => $selectedAttrs,
                                                'view'     => Theme::getThemeNamespace() . '::views.ecommerce.attributes.swatches-renderer'
                                            ]) !!}
                                        </div>
                                    @endif

                                    {!! render_product_options($product) !!}

                                    <div class="number-items-available mb-3">
                                        @if ($product->isOutOfStock())
                                            <span class="text-danger">({{ __('Out of stock') }})</span>
                                        @else
                                            @if (!$productVariation)
                                                <span class="text-danger">({{ __('Not available') }})</span>
                                            @else
                                                @if ($productVariation->isOutOfStock())
                                                    <span class="text-danger">({{ __('Out of stock') }})</span>
                                                @elseif  (!$productVariation->with_storehouse_management || $productVariation->quantity < 1)
                                                    <span class="text-success">({{ __('Available') }})</span>
                                                @elseif ($productVariation->quantity)
                                                    <span class="text-success">
                                                    @if ($productVariation->quantity != 1)
                                                            ({{ __(':number products available', ['number' => $productVariation->quantity]) }})
                                                        @else
                                                            ({{ __(':number product available', ['number' => $productVariation->quantity]) }})
                                                        @endif
                                                </span>
                                                @endif
                                            @endif
                                        @endif
                                    </div>

                                    {!! apply_filters(ECOMMERCE_PRODUCT_DETAIL_EXTRA_HTML, null, $product) !!}
                                    <div class="ps-product__shopping">
                                        <figure>
                                            <figcaption>{{ __('Quantity') }}</figcaption>
                                            <div class="form-group--number product__qty">
                                                <button class="up" type="button"><i class="icon-plus"></i></button>
                                                <button class="down" type="button"><i class="icon-minus"></i></button>
                                                <input class="form-control qty-input" type="number" name="qty" value="1" placeholder="1" min="1">
                                            </div>
                                        </figure>
                                        <input type="hidden" name="id" class="hidden-product-id" value="{{ ($product->is_variation || !$product->defaultVariation->product_id) ? $product->id : $product->defaultVariation->product_id }}"/>

                                        @if (EcommerceHelper::isCartEnabled())
                                            <button class="ps-btn ps-btn--black add-to-cart-button @if ($product->isOutOfStock()) btn-disabled @endif" type="submit" name="add_to_cart" value="1" @if ($product->isOutOfStock()) disabled @endif>{{ __('Add to cart') }}</button>
                                            @if (EcommerceHelper::isQuickBuyButtonEnabled())
                                                <button class="ps-btn add-to-cart-button @if ($product->isOutOfStock()) btn-disabled @endif" type="submit" name="checkout" value="1" @if ($product->isOutOfStock()) disabled @endif>{{ __('Buy Now') }}</button>
                                            @endif
                                        @endif
                                        <div class="ps-product__actions">
                                            @if (EcommerceHelper::isWishlistEnabled())
                                                <a class="js-add-to-wishlist-button" href="#" data-url="{{ route('public.wishlist.add', $product->id) }}"><i class="icon-heart"></i></a>
                                            @endif
                                            @if (EcommerceHelper::isCompareEnabled())
                                                <a class="js-add-to-compare-button" href="#" data-url="{{ route('public.compare.add', $product->id) }}" title="{{ __('Compare') }}"><i class="icon-chart-bars"></i></a>
                                            @endif
                                        </div>
                                    </div>
                                </form>
                                <div class="ps-product__specification">

                                    <p @if (!$product->sku) style="display: none" @endif><strong>{{ __('SKU') }}:</strong> <span id="product-sku">{{ $product->sku }}</span></p>
                                    @if ($product->categories->count())
                                        <p class="categories"><strong> {{ __('Categories') }}:</strong>
                                            @foreach($product->categories as $category)
                                                <a href="{{ $category->url }}">{!! BaseHelper::clean($category->name) !!}</a>@if (!$loop->last),@endif
                                            @endforeach
                                        </p>
                                    @endif

                                    @if ($product->tags->count())
                                        <p class="tags"><strong> {{ __('Tags') }}:</strong>
                                            @foreach($product->tags as $tag)
                                                <a href="{{ $tag->url }}">{{ $tag->name }}</a>@if (!$loop->last),@endif
                                            @endforeach
                                        </p>
                                    @endif
                                </div>
                                <div>
                                    <span>{{ __('Share:') }}</span>

                                    {!! Theme::renderSocialSharing($product->url, SeoHelper::getDescription(), $product->image) !!}
                                </div>
                            </div>
                        </div>
                        <div class="ps-product__content ps-tab-root">
                            <ul class="ps-tab-list">
                                <li class="active"><a href="#tab-description">{{ __('Description') }}</a></li>
                                @if (EcommerceHelper::isReviewEnabled())
                                    <li><a href="#tab-reviews">{{ __('Reviews') }}&nbsp;({{ $product->reviews_count }})</a></li>
                                @endif
                                @if (is_plugin_active('marketplace') && $product->store_id)
                                    <li><a href="#tab-vendor">{{ __('Vendor') }}</a></li>
                                @endif
                                @if (is_plugin_active('faq'))
                                    @if (count($product->faq_items) > 0)
                                        <li><a href="#tab-faq">{{ __('Questions and Answers') }}</a></li>
                                    @endif
                                @endif
                            </ul>
                            <div class="ps-tabs">
                                <div class="ps-tab active" id="tab-description">
                                    <div class="ps-document">
                                        <div class="ck-content">
                                            {!! BaseHelper::clean($product->content) !!}
                                        </div>
                                        <br />
                                        {!! apply_filters(BASE_FILTER_PUBLIC_COMMENT_AREA, null, $product) !!}
                                    </div>
                                </div>
                                @if (EcommerceHelper::isReviewEnabled())
                                    <div class="ps-tab" id="tab-reviews">
                                        @include('plugins/ecommerce::themes.includes.reviews', ['reviewButtonClass' => 'ps-btn'])
                                    </div>
                                @endif

                                @if (is_plugin_active('marketplace') && $product->store_id)
                                    <div class="ps-tab" id="tab-vendor">
                                        <h4>{{ $product->store->name }}</h4>
                                        <div>
                                            {!! BaseHelper::clean($product->store->content) !!}
                                        </div>

                                        <a href="{{ $product->store->url }}" class="more-products">
                                            {{ __('More Products from :store',  ['store' => $product->store->name]) }}
                                        </a>
                                    </div>
                                @endif

                                @if (is_plugin_active('faq') && count($product->faq_items) > 0)
                                    <div class="ps-tab" id="tab-faq">
                                        <div class="accordion" id="faq-accordion">
                                            @foreach($product->faq_items as $faq)
                                                <div class="card">
                                                    <div class="card-header" id="heading-faq-{{ $loop->index }}">
                                                        <h2 class="mb-0">
                                                            <button class="btn btn-link btn-block text-left @if (!$loop->first) collapsed @endif" type="button" data-toggle="collapse" data-target="#collapse-faq-{{ $loop->index }}" aria-expanded="true" aria-controls="collapse-faq-{{ $loop->index }}">
                                                                {!! BaseHelper::clean($faq[0]['value']) !!}
                                                            </button>
                                                        </h2>
                                                    </div>

                                                    <div id="collapse-faq-{{ $loop->index }}" class="collapse @if ($loop->first) show @endif" aria-labelledby="heading-faq-{{ $loop->index }}" data-parent="#faq-accordion">
                                                        <div class="card-body">
                                                            {!! BaseHelper::clean($faq[1]['value']) !!}
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="ps-page__right">
                    <aside class="widget widget_product widget_features">
                        @for ($i = 1; $i <= 5; $i++)
                            @if (theme_option('product_feature_' . $i . '_title'))
                                <p><i class="{{ theme_option('product_feature_' . $i . '_icon') }}"></i> {{ theme_option('product_feature_' . $i . '_title') }}</p>
                            @endif
                        @endfor
                    </aside>
                    @if (is_plugin_active('ads'))
                        <aside class="widget">
                            {!! AdsManager::display('product-sidebar') !!}
                        </aside>
                    @endif
                </div>
            </div>

            @php
                $crossSellProducts = get_cross_sale_products($product, 7);
            @endphp
            @if (count($crossSellProducts) > 0)
                {!! Theme::partial('cross-sell-products', compact('crossSellProducts')) !!}
            @endif

            <div class="ps-section--default">
                <div class="ps-section__header">
                    <h3>{{ __('Related products') }}</h3>
                </div>
                <div class="ps-section__content">
                    <div class="ps-carousel--responsive owl-slider"
                         data-owl-auto="true"
                         data-owl-loop="false"
                         data-owl-speed="10000"
                         data-owl-gap="0"
                         data-owl-nav="false"
                         data-owl-dots="true"
                         data-owl-item="7"
                         data-owl-item-xs="2"
                         data-owl-item-sm="2"
                         data-owl-item-md="3"
                         data-owl-item-lg="4"
                         data-owl-item-xl="6"
                         data-owl-duration="1000"
                         data-owl-mousedrag="on"
                    >
                        @foreach(get_related_products($product, 7) as $relatedProduct)
                            <div class="ps-product">
                                {!! Theme::partial('product-item', ['product' => $relatedProduct]) !!}
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
    </div>
</div>
