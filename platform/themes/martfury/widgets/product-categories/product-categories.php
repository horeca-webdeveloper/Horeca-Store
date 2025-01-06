<?php

use Botble\Ecommerce\Facades\ProductCategoryHelper;
use Botble\Widget\AbstractWidget;
use Illuminate\Support\Collection;

class ProductCategoriesWidget extends AbstractWidget
{
    public function __construct()
    {
        parent::__construct([
            'name' => __('Product Categories'),
            'description' => __('List of product categories'),
            'categories' => [],
        ]);
    }

    protected function data(): array|Collection
    {
        $categoryIds = $this->getConfig('categories');

        if (empty($categoryIds)) {
            return [
                'categories' => [],
            ];
        }

        $categories = ProductCategoryHelper::getProductCategoriesWithUrl($categoryIds);

        return [
            'categories' => $categories,
        ];
    }

    protected function requiredPlugins(): array
    {
        return ['ecommerce'];
    }
}
