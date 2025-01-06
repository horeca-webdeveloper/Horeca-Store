@include(Theme::getThemeNamespace('views.ecommerce.includes.product-items' . (request()->get('layout') == 'list' ? '-list' : '-grid')))
