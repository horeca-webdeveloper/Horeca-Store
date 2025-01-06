<div class="row">
    @foreach($stores as $store)
        <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6 col-12 ">
            <article class="ps-block--store-2">
                <div class="ps-block__content bg--cover" data-background="{{ asset('vendor/core/plugins/marketplace/img/default-store-banner.png') }}">
                    <figure>
                        <h4>{{ $store->name }}</h4>
                        @if (EcommerceHelper::isReviewEnabled())
                            <div class="rating_wrap">
                                <div class="rating">
                                    <div class="product_rate" style="width: {{ $store->reviews->avg('star') * 20 }}%"></div>
                                </div>
                                <span class="rating_num">({{ $store->reviews->count() }})</span>
                            </div>
                        @endif
                        @if(! MarketplaceHelper::hideStoreAddress() && $store->full_address)
                            <p>{{ $store->full_address }}</p>
                        @endif
                        @if (!MarketplaceHelper::hideStorePhoneNumber() && $store->phone)
                            <p><i class="icon-telephone"></i>&nbsp;{{ $store->phone }}</p>
                        @endif
                        @if (!MarketplaceHelper::hideStoreEmail() && $store->email)
                            <p><i class="icon-envelope"></i>&nbsp;<a href="mailto:{{ $store->email }}">{{ $store->email }}</a></p>
                        @endif
                    </figure>
                </div>
                <div class="ps-block__author">
                    <a class="ps-block__user" href="{{ $store->url }}">
                        <img src="{{ RvMedia::getImageUrl($store->logo, 'small', false, RvMedia::getDefaultImage()) }}" alt="{{ $store->name }}">
                    </a>
                    <a class="ps-btn" href="{{ $store->url }}">{{ __('Visit Store') }}</a>
                </div>
            </article>
        </div>
@endforeach
