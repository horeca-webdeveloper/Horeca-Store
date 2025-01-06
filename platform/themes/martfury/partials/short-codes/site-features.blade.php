<div class="ps-site-features">
    <div class="ps-container">
        <div class="ps-block--site-features">
            @for ($i = 1; $i <= 5; $i++)
                @if (clean($shortcode->{'title' . $i}))
                    <div class="ps-block__item">
                        <div class="ps-block__left"><i class="{!! BaseHelper::clean($shortcode->{'icon' . $i}) !!}"></i></div>
                        <div class="ps-block__right">
                            <h4>{!! BaseHelper::clean($shortcode->{'title' . $i}) !!}</h4>
                            <p>{!! BaseHelper::clean($shortcode->{'subtitle' . $i}) !!}</p>
                        </div>
                    </div>
                @endif
            @endfor
        </div>
    </div>
</div>
