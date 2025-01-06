<div class="mt-40 mb-40">
    <div class="ps-container">
        @if($title)
            <div class="ps-top-categories">
                <div class="ps-section__header">
                    <h3>{!! BaseHelper::clean($title) !!}</h3>
                </div>
            </div>
        @endif
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
            @foreach($brands as $brand)
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
</div>
