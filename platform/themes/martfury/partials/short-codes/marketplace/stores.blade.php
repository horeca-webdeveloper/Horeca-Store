<div class="ps-product-list mt-40 mb-40">
    <div class="ps-container">
        <div class="ps-section__header">
            <h3>{{ $shortcode->title }}</h3>
            <ul class="ps-section__links">
                <li><a href="{{ route('public.stores') }}">{{ __('View All') }}</a></li>
            </ul>
        </div>
        <div class="ps-section__content">
            <section class="ps-store-list">
                @include(Theme::getThemeNamespace('views.marketplace.includes.store-items'))
            </section>
        </div>
    </div>
</div>

