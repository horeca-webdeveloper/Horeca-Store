<div class="ps-newsletter mt-40">
    <div class="ps-container newsletter-form">
        <form class="ps-form--newsletter" method="post" action="{{ route('public.newsletter.subscribe') }}">
            @csrf
            <div class="row">
                <div class="col-xl-5">
                    <div class="ps-form__left">
                        <h3>{!! BaseHelper::clean($title) !!}</h3>
                        @if ($description)
                            <p>{!! BaseHelper::clean($description) !!}</p>
                        @endif
                        @if ($subtitle)
                            <p>{!! BaseHelper::clean($subtitle) !!}</p>
                        @endif
                    </div>
                </div>
                <div class="col-xl-7">
                    <div class="ps-form__right">
                        @csrf
                        <div class="form-group--nest">
                            <input class="form-control" name="email" type="email" placeholder="{{ __('Email address') }}">
                            <button class="ps-btn" type="submit">{{ __('Subscribe') }}</button>
                        </div>

                        {!! apply_filters('form_extra_fields_render', null, \Botble\Newsletter\Forms\Fronts\NewsletterForm::class) !!}
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
