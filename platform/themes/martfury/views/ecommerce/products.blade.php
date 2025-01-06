<div class="ps-page--shop">
    @if (theme_option('show_featured_brands_on_products_page', 'yes') == 'yes')
        <div class="mt-40">
            <div class="ps-shop-brand ps-carousel--responsive owl-slider"
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
                @foreach(get_featured_brands() as $brand)
                    @if($brand->website)
                        <a href="{{ $brand->website }}">
                    @endif
                    <img src="{{ RvMedia::getImageUrl($brand->logo, null, false, RvMedia::getDefaultImage()) }}" alt="{{ $brand->name }}" loading="lazy"/>
                    @if($brand->website)
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    <div class="ps-layout--shop">
        <div class="ps-layout__left">
            @include(Theme::getThemeNamespace() . '::views.ecommerce.includes.filters')
        </div>
        <div class="ps-layout__right">
            @if (theme_option('show_recommend_items_on_products_page', 'yes') == 'yes')
                <div class="ps-block--shop-features">
                    <div class="ps-block__header">
                        <h3>{{ __('Recommended Items') }}</h3>
                        <div class="ps-block__navigation">
                            <a class="ps-carousel__prev" href="#recommended-products">
                                <i class="icon-chevron-left"></i>
                            </a>
                            <a class="ps-carousel__next" href="#recommended-products">
                                <i class="icon-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                    <div class="ps-block__content">
                        <div class="ps-section__content">
                            <div id="recommended-products"
                                 class="ps-carousel--responsive owl-slider"
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
                                @foreach(get_featured_products() as $product)
                                    <div class="ps-product">
                                        {!! Theme::partial('product-item', compact('product')) !!}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            @include(Theme::getThemeNamespace('views.ecommerce.includes.products-list'))
        </div>
    </div>
</div>
