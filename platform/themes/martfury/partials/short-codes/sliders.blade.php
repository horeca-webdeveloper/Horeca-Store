@if (count($sliders) > 0 && $sliders->loadMissing('metadata'))
    <div class="ps-home-banner ps-home-banner--1">
        <div class="ps-container">
            @php $ads = get_ads_keys_from_shortcode($shortcode); @endphp
            @if (is_plugin_active('ads') && (AdsManager::locationHasAds('top-slider-image-1') || AdsManager::locationHasAds('top-slider-image-2') || count($ads)))
                <div class="ps-section__left">
                    <div class="ps-carousel--nav-inside owl-slider" data-owl-auto="true" data-owl-loop="true"
                        data-owl-speed="5000" data-owl-gap="0" data-owl-nav="true"
                        data-owl-dots="true" data-owl-item="1" data-owl-item-xs="1"
                        data-owl-item-sm="1" data-owl-item-md="1" data-owl-item-lg="1"
                        data-owl-duration="1000" data-owl-mousedrag="on"
                        data-owl-animate-in="fadeIn" data-owl-animate-out="fadeOut">
                        @foreach($sliders as $slider)
                            <div class="ps-banner bg--cover">
                                @php
                                    $img = RvMedia::getImageUrl($slider->image, null, false, RvMedia::getDefaultImage());
                                    $tabletImage = $slider->getMetaData('tablet_image', true) ?: $img;
                                    $mobileImage = $slider->getMetaData('mobile_image', true) ?: $tabletImage;
                                @endphp
                                <picture>
                                    <img src="{{ $img }}" alt="slider" loading="eager" />
                                    <source srcset="{{ $img }}" media="(min-width: 1200px)" />
                                    <source srcset="{{ RvMedia::getImageUrl($tabletImage) }}" media="(min-width: 768px)" />
                                    <source srcset="{{ RvMedia::getImageUrl($mobileImage) }}" media="(max-width: 767px)" />
                                </picture>
                                @if ($slider->link)
                                    <a class="ps-banner__overlay" href="{{ url($slider->link) }}"></a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="ps-section__right">
                    @if (! empty($ads))
                        @foreach ($ads as $key)
                            <div class="ps-collection">
                                {!! AdsManager::displayAds($key) !!}
                            </div>
                        @endforeach
                    @else
                        @if (AdsManager::locationHasAds('top-slider-image-1'))
                            <div class="ps-collection">
                                {!! AdsManager::display('top-slider-image-1') !!}
                            </div>
                        @endif
                        @if (AdsManager::locationHasAds('top-slider-image-2'))
                            <div class="ps-collection">
                                {!! AdsManager::display('top-slider-image-2') !!}
                            </div>
                        @endif
                    @endif
                </div>
            @else
                <div class="ps-carousel--nav-inside owl-slider" data-owl-auto="true" data-owl-loop="true" data-owl-speed="5000" data-owl-gap="0" data-owl-nav="true" data-owl-dots="true" data-owl-item="1" data-owl-item-xs="1" data-owl-item-sm="1" data-owl-item-md="1" data-owl-item-lg="1" data-owl-duration="1000" data-owl-mousedrag="on" data-owl-animate-in="fadeIn" data-owl-animate-out="fadeOut">
                    @foreach($sliders as $slider)
                        <div class="ps-banner bg--cover" data-background="{{ RvMedia::getImageUrl($slider->image, null, false, RvMedia::getDefaultImage()) }}">
                            @if ($slider->link)
                                <a class="ps-banner__overlay" href="{{ url($slider->link) }}"></a>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endif
