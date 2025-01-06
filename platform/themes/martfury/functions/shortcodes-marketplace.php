<?php

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Facades\Assets;
use Botble\Base\Facades\Html;
use Botble\Ecommerce\Facades\EcommerceHelper;
use Botble\Marketplace\Models\Store;
use Botble\Shortcode\Compilers\Shortcode;
use Botble\Theme\Facades\Theme;

if (is_plugin_active('marketplace')) {
    Assets::addStylesDirectly('vendor/core/core/base/libraries/tagify/tagify.css');

    add_shortcode('marketplace-stores', __('Marketplace Stores'), __('Marketplace Stores'), function (Shortcode $shortcode) {
        $storeIds = [];

        if ($shortcode->stores) {
            $storeIds = explode(',', $shortcode->stores);
        }

        if (empty($storeIds)) {
            return null;
        }

        $with = ['slugable'];
        if (EcommerceHelper::isReviewEnabled()) {
            $with['reviews'] = function ($query) {
                $query->where([
                    'ec_products.status' => BaseStatusEnum::PUBLISHED,
                    'ec_reviews.status' => BaseStatusEnum::PUBLISHED,
                ]);
            };
        }

        $stores = Store::query()
            ->wherePublished()
            ->whereIn('id', $storeIds)
            ->with($with)
            ->withCount([
                'products' => function ($query) {
                    $query->wherePublished();
                },
            ])
            ->orderByDesc('created_at')
            ->get();

        return Theme::partial('short-codes.marketplace.stores', compact('shortcode', 'stores'));
    });

    shortcode()->setAdminConfig('marketplace-stores', function (array $attributes) {
        $stores = Store::query()
            ->wherePublished()
            ->orderBy('name')
            ->pluck('name', 'id');

        return Html::script('vendor/core/core/base/libraries/tagify/tagify.js') .
            Html::script('vendor/core/core/base/js/tags.js') .
            Theme::partial('short-codes.marketplace.stores-admin-config', compact('attributes', 'stores'));
    });
}
