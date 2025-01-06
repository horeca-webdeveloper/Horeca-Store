{!! Theme::partial('header-meta') !!}
<body @if (BaseHelper::siteLanguageDirection() == 'rtl') dir="rtl" @endif @if (Theme::get('pageId')) id="{{ Theme::get('pageId') }}" @endif>
<div class="ps-page--comming-soon">
    <div class="container">
        <div class="ps-page__header">
            <h1>{{ SeoHelper::getTitle() }}</h1>
        </div>
        <div>{!! Theme::content() !!}</div>
        <div class="ps-page__footer">
            {!! Theme::partial('social-links') !!}
        </div>
    </div>
</div>

{!! Theme::footer() !!}
</body>
</html>

