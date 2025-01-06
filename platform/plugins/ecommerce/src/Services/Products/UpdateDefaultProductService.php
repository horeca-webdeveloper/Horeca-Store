<?php

namespace Botble\Ecommerce\Services\Products;

use Botble\Ecommerce\Models\Product;

class UpdateDefaultProductService
{
    protected array $columns = [
        'barcode',
        'sku',
        'price',
        'sale_type',
        'sale_price',
        'start_date',
        'end_date',
        'length',
        'depth',
        'shipping_height',
        'shipping_length',
        'shipping_depth',
        'shipping_width',
        'width',
        'height',
        'weight',
        'quantity',
        'allow_checkout_when_out_of_stock',
        'with_storehouse_management',
    ];

    public function execute(Product $product)
    {
        $parent = $product->original_product;

        if (! $parent->id) {
            return null;
        }

        $this->updateColumns($parent, $product);

        $parent->save();

        return $parent;
    }

    public function setColumns(array $columns): static
    {
        $this->columns = $columns;

        return $this;
    }

    public function updateColumns(Product $parent, Product $product): Product
    {
        $data = $this->columns;

        foreach ($data as $item) {
            if ($item === 'sku' && $parent->sku) {
                continue;
            }

            $parent->{$item} = $product->{$item};
        }

        if ($parent->sale_price > $parent->price) {
            $parent->sale_price = null;
        }

        if ($parent->sale_type === 0) {
            $parent->start_date = null;
            $parent->end_date = null;
        }

        return $parent;
    }
}
