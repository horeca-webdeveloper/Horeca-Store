@php
    SeoHelper::setTitle(__('404 - Not found'));
    Theme::fireEventGlobalAssets();
@endphp

{!! Theme::partial('header') !!}

<div class="ps-page--404">
    <div class="container">
        <div class="ps-section__content mt-40 mb-40">
            <img src="{{ Theme::asset()->url('img/404.jpg') }}" alt="404">
            <h3>{{ __('Ohh! Page not found') }}</h3>
            <p>{{ __("It seems we can't find what you're looking for. Perhaps searching can help or go back to") }}<a href="{{ BaseHelper::getHomepageUrl() }}"> {{ __('Homepage') }}</a></p>

            @if (is_plugin_active('ecommerce'))
                <form class="ps-form--widget-search" action="{{ route('public.products') }}" method="get">
                    <input class="form-control" type="text" name="q" placeholder="{{ __('Search...') }}">
                    <button><i class="icon-magnifier"></i></button>
                </form>
            @endif
        </div>
    </div>
</div>

{!! Theme::partial('footer') !!}


