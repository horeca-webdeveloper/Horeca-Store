<header class="header header--mobile" data-sticky="true">
    <div class="header__top">
        <div class="header__left">
            <p>{{ theme_option('welcome_message') }}</p>
        </div>
        @if (is_plugin_active('ecommerce'))
            <div class="header__right">
                <ul class="navigation__extra">
                    @if (EcommerceHelper::isOrderTrackingEnabled())
                        <li><a href="{{ route('public.orders.tracking') }}">{{ __('Track your order') }}</a></li>
                    @endif
                    @php $currencies = get_all_currencies(); @endphp
                    @if (count($currencies) > 1)
                        <li>
                            <div class="ps-dropdown"><a href="#"><span>{{ get_application_currency()->title }}</span></a>
                                <ul class="ps-dropdown-menu">
                                    @foreach ($currencies as $currency)
                                        <li><a href="{{ route('public.change-currency', $currency->title) }}"><span>{{ $currency->title }}</span></a></li>
                                    @endforeach
                                </ul>
                            </div>
                        </li>
                    @endif
                    @if (is_plugin_active('language'))
                        {!! Theme::partial('language-switcher') !!}
                    @endif
                </ul>
            </div>
        @endif
    </div>
    <div class="navigation--mobile">
        <div class="navigation__left"><a class="ps-logo" href="{{ route('public.index') }}"><img src="{{ RvMedia::getImageUrl(theme_option('logo')) }}" alt="{{ theme_option('site_title') }}" style="max-height: {{ theme_option('logo_height', 40) }}" /></a></div>
        @if (is_plugin_active('ecommerce'))
            <div class="navigation__right">
                <div class="header__actions">
                    {!! apply_filters('before_theme_header_mobile_actions', null) !!}
                    <div class="ps-cart--mini">
                        <a class="header__extra btn-shopping-cart" href="{{ route('public.cart') }}">
                            <i class="icon-bag2"></i><span><i>{{ Cart::instance('cart')->count() }}</i></span>
                        </a>
                        <div class="ps-cart--mobile">
                            {!! Theme::partial('cart') !!}
                        </div>
                    </div>
                    {!! apply_filters('after_theme_header_mobile_actions', null) !!}
                    <div class="ps-block--user-header">
                        <div class="ps-block__left"><a href="{{ route('customer.overview') }}"><i class="icon-user"></i></a></div>
                    </div>
                </div>
            </div>
        @endif
    </div>
    @if (is_plugin_active('ecommerce'))
        <div class="ps-search--mobile">
            <form class="ps-form--search-mobile" action="{{ route('public.products') }}" data-ajax-url="{{ route('public.ajax.search-products') }}" method="get">
                <div class="form-group--nest position-relative">
                    <input class="form-control input-search-product" name="q" value="{{ BaseHelper::stringify(request()->query('q')) }}" type="text" autocomplete="off" placeholder="{{ __('Search something...') }}">
                    <div class="spinner-icon">
                        <i class="fa fa-spin fa-spinner"></i>
                    </div>
                    <button type="submit"><i class="icon-magnifier"></i></button>
                    <div class="ps-panel--search-result"></div>
                </div>
            </form>
        </div>
    @endif
</header>
