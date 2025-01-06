<div class="ps-page--shop">
    <div class="ps-layout--shop">
        <div class="ps-layout__left">
            @include(Theme::getThemeNamespace() . '::views.ecommerce.includes.filters')
        </div>
        <div class="ps-layout__right">
            <div class="ps-block--shop-features">
                <div class="ps-block__header">
                    <h1 class="h1">{{ $category->name }}</h1>
                </div>
                @if ($category->description)
                    <div class="ps-block__content">
                        <div class="ps-section__content">
                            {!! BaseHelper::clean($category->description) !!}
                        </div>
                    </div>
                @endif
            </div>
            @include(Theme::getThemeNamespace('views.ecommerce.includes.products-list'))
        </div>
    </div>
</div>
