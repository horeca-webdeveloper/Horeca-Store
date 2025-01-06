<div class="ps-contact-info">
    <div class="container">
        <div class="ps-section__header">
            <h3>{!! BaseHelper::clean($title) !!}</h3>
        </div>
        <div class="ps-section__content">
            <div class="row">
                @for ($i = 1; $i <= 6; $i++)
                    @if ($boxTitle = theme_option('contact_info_box_' . $i . '_title'))
                        <div class="col-md-4 col-sm-6 col-12">
                            <div class="ps-block--contact-info">
                                <h4>{{ $boxTitle }}</h4>
                                @php
                                    $boxValue = theme_option('contact_info_box_' . $i . '_details');
                                    $boxSubtitle = theme_option('contact_info_box_' . $i . '_subtitle');
                                @endphp

                                @if ($boxValue || $boxSubtitle)
                                    <p>
                                        <span class="d-block">{{ filter_var($boxSubtitle, FILTER_VALIDATE_EMAIL) ? Html::mailto($boxSubtitle) : $boxSubtitle }}</span>
                                        <span>{{ filter_var($boxValue, FILTER_VALIDATE_EMAIL) ? Html::mailto($boxValue) : $boxValue }}</span>
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endif
                @endfor
            </div>
        </div>
    </div>
</div>
