<div class="ps-download-app">
    <div class="ps-container">
        <div class="ps-block--download-app">
            <div class="container">
                <div class="row">
                    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12 ">
                        <div class="ps-block__thumbnail">
                            <img src="{{ RvMedia::getImageUrl($screenshot) }}" alt="screenshot">
                        </div>
                    </div>
                    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12 ">
                        <div class="ps-block__content">
                            <h3>{!! BaseHelper::clean($title) !!}</h3>
                            @if ($description)
                                <p>{!! BaseHelper::clean($description) !!}</p>
                            @endif
                            @if ($subtitle)
                                <p>{!! BaseHelper::clean($subtitle) !!}</p>
                            @endif
                            <form class="ps-form--download-app" action="{{ route('public.ajax.send-download-app-links') }}" method="post">
                                @csrf
                                <div class="form-group--nest">
                                    <input class="form-control" type="email" name="email" placeholder="{{ __('Email Address') }}">

                                    <button class="ps-btn" type="submit">{{ __('Subscribe') }}</button>
                                </div>

                                @if ($extraFields = apply_filters('form_extra_fields_render', null, \Botble\Newsletter\Forms\Fronts\NewsletterForm::class))
                                    <div style="margin-top: 15px;">{!! $extraFields !!}</div>
                                @endif
                            </form>
                            @if ($androidAppUrl || $iosAppUrl)
                                <p class="download-link">
                                    @if ($androidAppUrl)
                                        <a href="{{ (string) $androidAppUrl }}"><img src="{{ Theme::asset()->url('img/google-play.png') }}" alt="{{ __('Google Play') }}"></a>
                                    @endif

                                    @if ($iosAppUrl)
                                        <a href="{{ (string) $iosAppUrl }}"><img src="{{ Theme::asset()->url('img/app-store.png') }}" alt="{{ __('App Store') }}"></a>
                                    @endif
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
